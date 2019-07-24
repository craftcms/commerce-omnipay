# Release Notes for Omnipay integration package for Craft Commerce

## 2.1.0.1 - 2019-07-24

### Changed
- Updated changelog with missing changes for 2.1.0

## 2.1.0 - 2019-07-24

### Changed
- Update Craft Commerce requirements to allow for Craft Commerce 3.

## 2.0.1 - 2019-06-06

### Fixed
- Fix a bug where using gateway via CLI would break. ([#9] (https://github.com/craftcms/commerce-omnipay/issues/9))

## 2.0.0 - 2019-03-04

### Added
- `craft\commerce\omnipay\base\Gateway::createOmnipayGateway()` method for creating the Omnipay gateway using Craft's Guzzle client.

### Changed
- Package now uses Omnipay v3.

## 1.0.2 - 2018-10-16

### Fixed
- Fized a bug where `notifyUrl` parameter was set even if gateway did not support webhooks.

## 1.0.1 - 2018-05-30

### Changed
- Package now requires Commerce 2.0.0-beta.5
- Added additional user ID param to `Gateway::createPaymentSource()` for compatibility with Commerce 2.0.0-beta.5

## 1.0.0 - 2018-04-03

- Initial release.
