=== Woo Earn Share ===
Contributors: lucius0101
Donate link: http://bit.ly/wooes
Tags: woocommerce, friend code, referral, affiliate
Requires at least: 3.5
Tested up to: 6.0.2
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Let your users share their own codes to earn discounts.

== Description ==

Let your users share their own codes to earn discounts!

When someone uses a referral code when purchasing in your shop and the order is "Completed", the code owner will receive a percentage (set in the dashboard admin of WordPress), which will be added to their balance. The balance can then be used on the next purchase.

*See the FAQ for more details*

**Feature:**
You can enable returning the money to a user who used their balance but had the purchase refunded, canceled, or failed.

**Shortcodes!**
[wooes_user_balance] - Shows the current user balance
[wooes_user_code] - Shows the current user code


**Filters!**
#### wooes_user_balance
##### Filters the user's balance

- $balance float The user's balance.
- $user_id integer The user ID.

<br>

#### wooes_get_user_code
##### Filters the user's code

- $code string The code fetched from the user.
- $user_id integer The user ID.
- $format boolean Whether or not the code should be formatted.

<br>

#### wooes_get_user_by_code
##### Filters the user by the code

- $user \WP_User|false The fetched user of false if none.
- $code string The code used to search the user.

<br>

#### wooes_new_balance
##### Filters the new user's balance

- $new_balance float The new balance value.
- $money float The money being added.
- $old_balance float The previous value.
- $giving_back boolean Whether it's giving the money back or not.

<br>

#### wooes_new_balance
##### Filters the new user's balance

- $new_balance float The new balance value.
- $money float The money being added.
- $old_balance float The previous value.
- $giving_back float Whether it's giving the money back or not.

<br>

#### wooes_generate_new_referral_code
##### Filters the newly generated code

- $code string The randomly generated code.
- $length integer The length of the code, from settings.
- $alphanumeric boolean Whether it's an alphanumeric code, from settings.

<br>

#### wooes_format_code
##### Filters the formatted code

- $code string The code.
	
== Frequently Asked Questions ==

= How is the balance used? =

If a user makes a purchase that is less than their balance, i.e., their balance is greater than what they are trying to buy, the purchase amount will be 1, to avoid problems with payment methods when trying to finalize a purchase costing 0.00.

*Example:*
User Balance: USD 100.00
User Cart: USD 50.00
Discount based on balance: USD 49.00
Total purchase: USD 1.00

And then, the user will have a USD 51.00 balance.

== Installation ==

1. Upload `woo-earn-sharing` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Settings are in Woocommerce -> Wooes
1. If User Code Page returning not found, simply re-save Permalinks

== Screenshots ==

1. Woocommerce Menu
1. Admin

== Changelog ==
= 2.0 =
* Many code improvements
* Race-condition prevention
= 1.1 =
* Added Max Refund option
* Minor fixes
= 1.0 =
* First release
