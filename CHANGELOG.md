# Release Notes for Omnipay integration package for Craft Commerce

## Unreleased

- Added Craft CMS 5 and Craft Commerce 5 compatibility.

## 4.0.0.1 - 2023-03-28

### Fixed
- Fixed a PHP error that could occur when retrieving data from a gateway response. ([#49](https://github.com/craftcms/commerce-mollie/issues/49))

## 4.0.0 - 2022-05-04

### Added
- Added Craft CMS 4 and Craft Commerce 4 compatibility.

### Changed
- `sendCartInfo` setting now supports environment variables.

## 3.0.3.1 - 2021-11-03

### Fixed
- Fixed a bug where zero-value line items were being sent to the gateway.

## 3.0.3 - 2021-11-03

### Fixed
- Fixed a bug where zero-value line items were being sent to the gateway. ([#24](https://github.com/craftcms/commerce-omnipay/issues/24))

## 3.0.2 - 2021-06-21

### Fixed
- Fixed an error that could occur when using the `OffsitePaymentForm` model. ([#17](https://github.com/craftcms/commerce-omnipay/issues/17))

## 3.0.1 - 2021-06-17

### Fixed
- Fixed a bug where fallback billing names were being set incorrectly. ([#17](https://github.com/craftcms/commerce-omnipay/issues/17))

## 3.0.0 - 2021-04-20

### Changed
- Package now requires Commerce 3.3
- Guzzle 7 is now required.

## 2.1.1

### Changed
- Changed Guzzle version requirements to also allow 2.x versions.

## 2.1.0.1 - 2019-07-24

### Changed
- Updated changelog with missing changes for 2.1.0

## 2.1.0 - 2019-07-24

### Changed
- Update Craft Commerce requirements to allow for Craft Commerce 3.

## 2.0.1 - 2019-06-06

### Fixed
- Fix a bug where using gateway via CLI would break. ([#9](https://github.com/craftcms/commerce-omnipay/issues/9))

## 2.0.0 - 2019-03-04

### Added
- `craft\commerce\omnipay\base\Gateway::createOmnipayGateway()` method for creating the Omnipay gateway using Craft's Guzzle client.

### Changed
- Package now uses Omnipay v3.

## 1.0.2 - 2018-10-16

### Fixed
- Fixed a bug where `notifyUrl` parameter was set even if gateway did not support webhooks.

## 1.0.1 - 2018-05-30

### Changed
- Package now requires Commerce 2.0.0-beta.5
- Added additional user ID param to `Gateway::createPaymentSource()` for compatibility with Commerce 2.0.0-beta.5

## 1.0.0 - 2018-04-03

- Initial release.
