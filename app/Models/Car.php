<?php

namespace App\Models;

use App\Rules\CanBeActive;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * A used car on the lot.
 *
 * Status is derived, there is no status column: `sold_at` set means sold,
 * otherwise `is_active` says whether the car is on the site or hidden.
 */
#[Fillable([
    'category_id', 'body_type_id', 'transmission_id', 'drivetrain_id',
    'title', 'trim', 'price', 'mileage', 'color', 'engine', 'description',
    'is_active', 'price_drop',
])]
class Car extends Model
{
    use HasFactory;

    public const STATUS_ON_SITE = 'on_site';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_SOLD = 'sold';

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'mileage' => 'integer',
            'sold_price' => 'integer',
            'is_active' => 'boolean',
            'price_drop' => 'boolean',
            'sold_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // The slug is unique and NOT NULL, so a placeholder is stored on insert
        // and replaced right after, once the id (the last part of the slug) exists.
        static::creating(function (Car $car): void {
            $car->slug = 'pending-'.Str::uuid();
        });

        static::created(function (Car $car): void {
            $car->forceFill(['slug' => $car->buildSlug()])->saveQuietly();
        });

        static::updating(function (Car $car): void {
            if ($car->isDirty(['title', 'trim'])) {
                $car->slug = $car->buildSlug();
            }
        });
    }

    // ---------------------------------------------------------------- relations

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<BodyType, $this> */
    public function bodyType(): BelongsTo
    {
        return $this->belongsTo(BodyType::class);
    }

    /** @return BelongsTo<Transmission, $this> */
    public function transmission(): BelongsTo
    {
        return $this->belongsTo(Transmission::class);
    }

    /** @return BelongsTo<Drivetrain, $this> */
    public function drivetrain(): BelongsTo
    {
        return $this->belongsTo(Drivetrain::class);
    }

    /** @return HasMany<CarPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(CarPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    // ------------------------------------------------------------------- scopes

    /** Cars shown on the public site: switched on and not sold. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('sold_at');
    }

    public function scopeSold(Builder $query): Builder
    {
        return $query->whereNotNull('sold_at');
    }

    /** Switched off by the store and not sold. */
    public function scopeHidden(Builder $query): Builder
    {
        return $query->where('is_active', false)->whereNull('sold_at');
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    // ------------------------------------------------------------------- status

    public function isSold(): bool
    {
        return $this->sold_at !== null;
    }

    public function getStatusAttribute(): string
    {
        return match (true) {
            $this->isSold() => self::STATUS_SOLD,
            $this->is_active => self::STATUS_ON_SITE,
            default => self::STATUS_HIDDEN,
        };
    }

    /**
     * Why this car cannot go on the site right now, or null when it can.
     */
    public function activationBlocker(): ?string
    {
        return CanBeActive::blocker($this->price, $this->photos()->count(), $this->isSold());
    }

    /**
     * Put the car on the site. Throws a validation error with the reason when
     * it has no price, no photo, or is sold.
     */
    public function activate(): static
    {
        if (($reason = $this->activationBlocker()) !== null) {
            throw ValidationException::withMessages(['is_active' => $reason]);
        }

        $this->forceFill(['is_active' => true])->save();

        return $this;
    }

    public function deactivate(): static
    {
        $this->forceFill(['is_active' => false])->save();

        return $this;
    }

    // -------------------------------------------------------------------- sales

    /**
     * Record the sale: stamps the date and price and takes the car off the site,
     * all in one transaction. The price defaults to the listed price. Marking a
     * car that is already sold changes nothing.
     */
    public function markAsSold(?CarbonInterface $soldAt = null, ?int $soldPrice = null): static
    {
        if ($this->isSold()) {
            return $this;
        }

        $soldAt ??= now();
        $soldPrice ??= $this->price;

        $errors = [];

        if ($soldAt->isFuture()) {
            $errors['sold_at'] = 'The sale date cannot be in the future.';
        }

        if ($soldPrice === null || $soldPrice < 1) {
            $errors['sold_price'] = 'Enter the sale price (at least $1).';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($soldAt, $soldPrice): void {
            $this->forceFill([
                'sold_at' => $soldAt,
                'sold_price' => $soldPrice,
                'is_active' => false,
            ])->save();
        });

        return $this;
    }

    /**
     * Undo a sale by mistake. The car stays hidden until the store switches it
     * back on.
     */
    public function reopen(): static
    {
        if (! $this->isSold()) {
            throw ValidationException::withMessages(['sold_at' => 'This car is not marked as sold.']);
        }

        DB::transaction(function (): void {
            $this->forceFill([
                'sold_at' => null,
                'sold_price' => null,
                'is_active' => false,
            ])->save();
        });

        return $this;
    }

    // ----------------------------------------------------------------- warranty

    /**
     * Massachusetts used-vehicle warranty (M.G.L. c. 90 § 7N¼), by mileage.
     * Null when there is no mileage or the car is past 125,000 miles.
     */
    public function maWarranty(): ?string
    {
        if ($this->mileage === null) {
            return null;
        }

        return match (true) {
            $this->mileage < 40_000 => '90 days or 3,750 miles',
            $this->mileage < 80_000 => '60 days or 2,500 miles',
            $this->mileage < 125_000 => '30 days or 1,250 miles',
            default => null,
        };
    }

    // ----------------------------------------------------------------- contact

    /** "2019 Jeep Compass Limited 4x4" (the version is optional). */
    public function fullName(): string
    {
        return trim(implode(' ', array_filter([$this->title, $this->trim])));
    }

    public function whatsappUrl(): string
    {
        return Setting::current()->whatsappUrl("Hi Mario, is the {$this->fullName()} still available?");
    }

    // -------------------------------------------------------------------- slug

    /** `title + trim + id`, e.g. `2019-jeep-compass-limited-4x4-12`. */
    public function buildSlug(): string
    {
        return Str::slug($this->fullName().' '.$this->getKey());
    }
}
