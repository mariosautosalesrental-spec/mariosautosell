# Mario's Auto — how to use the admin

This guide is for the store. Everything here is done in the admin at **`/admin`** (for example `https://www.your-site.com/admin`). It works well on a phone, in the lot.

## Sign in

Open `/admin` and sign in with your email and password. Mario and João have the same access.

## First day: add your makes

The website's "Shop by make" buttons come from **Categories**. Nothing is added for you, so start there:

1. Open **Categories** and tap **Add category**. Type a make, for example `Jeep`, and save.
2. Repeat for every make you sell.
3. Use the **up and down arrows** to choose the order of the buttons on the website.

A make only shows as a button on the website when it has at least one car on the site.

> You can also add a make while adding a car: tap the **+** next to **Category**.

## Add a car

1. Open **Cars** and tap **Add car**.
2. **Photos**: tap **Browse** (on a phone you can take a photo) or drop the files. The **first photo is the cover**. Drag the photos to change the order. Each photo can be up to 10 MB.
3. Fill in **Category**, **Name** (for example `2019 Jeep Compass`), **Version** (optional, for example `Limited 4x4`), **Price**, **Mileage**, **Body type**, **Transmission** and **Drivetrain**. Color, engine and description are optional.
4. The **price** is what the customer pays, including your dealer fees. Whole dollars, no cents. The website adds the note "plus tax, title & registration".
5. **Show on website** is on by default, so the car appears as soon as you save. It needs a **price and at least one photo**.
6. **"Price drop" badge**: turn it on to show the badge on the car card.
7. Tap **Create**. The preview on the right shows how the card will look.

### Save a car without showing it

Turn **Show on website** off. You can save a car without price or photos and finish it later.

## Hide or show a car

In the **Cars** list, use the **On site** switch. Turn it off to hide a car without deleting it. The switch refuses to turn on a car that has no price, no photo, or that is sold, and tells you why.

## A car was sold

1. In **Cars**, tap **Mark as sold** on the car (or open the car and use the button at the top).
2. Check the **sale date** (today by default) and the **sale price** (the listed price by default; change it if the car sold for less).
3. Confirm. The car **leaves the website right away**, but it stays in your list with the **Sold** badge and the sale price and date.

### Marked as sold by mistake?

Tap **Mark as available**. The sale is removed from your numbers and the car comes back **hidden**. Turn **Show on website** on again when you want it back on the site.

### Find sold cars

In the **Cars** list, use the filter and choose **Status → Sold**. You can also choose **On site** or **Hidden**, and combine it with the search box and the category filter.

## The dashboard

The first page of the admin shows:

- **Cars sold in the month** and **Sales in the month**, compared with the month before (a dash means there was nothing to compare with).
- **On the website**, **Hidden** and the **value of the cars on the website** (sold cars are not counted).
- A chart with the **last 6 months**.
- The **5 latest sales**.

The numbers update by themselves when you mark a car as sold. The month follows New York time.

## Store info

Open **Store info** to change the WhatsApp number, the phone, the opening hours and the address. They update everywhere on the website (header, "Visit the lot", footer, map and every WhatsApp button) as soon as you save.

- **WhatsApp number**: digits only, with the country code and no spaces or `+`, for example `19784174036`.
- **Hours**: free text, for example `9:00 AM – 6:00 PM` or `Closed`.

## Good to know

- A car with no photo shows "No photo yet" on the site: add a photo before showing it.
- Deleting a car also deletes its photos. If the car was sold, deleting it also removes the sale from the numbers. To keep the history, do not delete: use **Mark as sold**.
- A make can't be deleted while it still has cars (even sold or hidden ones).

## Checklist before the site goes live

- [ ] Real photos of the lot and of every car (the sample photos are for development only).
- [ ] Opening hours and the WhatsApp number confirmed in **Store info**.
- [ ] Your makes added in **Categories**.
- [ ] Privacy Policy, Terms of Use and Accessibility reviewed by an attorney.
- [ ] Domain name chosen and connected.
