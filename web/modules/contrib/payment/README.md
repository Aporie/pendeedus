# Payment
[![Build Status](https://travis-ci.org/bartfeenstra/drupal-payment.svg?branch=8.x-2.x)](https://travis-ci.org/bartfeenstra/drupal-payment)

## About
Payment allows you to create and configure payment methods and use those to
process other modules' payments.

In addition to this README file, also see PLUGINS.md and the 
[online handbook](http://drupal.org/node/1807610).

This project contains the following modules:

* Payment is an API to connect payment methods to modules that allow users to
  make payments. This means that it doesn't do much by itself, apart from
  providing other modules with the ability to make payments.
* Payment Form Field contains a field that displays a payment form when viewing
  the entity the field is attached to.
* Payment Reference Field contains a field that allow users to make a payment
  while adding a new entity.


## General configuration
To get started, you need to create at least one payment method. In order to do
that, you need to enable at least one module that provides payment method
plugins. Any other module that works with Payment and requires payments to be
made, such as a web shop, can then use this payment method automatically.
