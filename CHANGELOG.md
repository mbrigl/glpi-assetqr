# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-10-16

### Added
- Massive action "Print QR codes": prints the QR codes of all selected assets on one page.
- Configurable labels for printing: size, QR code size, font size, gap, text position, cutting frame.

### Fixed
- The "Print" button was not translated (GLPI core has no translation for it).

### Security
- Print page: Content Security Policy, no framing by other sites, "Back" link restricted to paths inside GLPI.
- At most 500 assets per print job; the print token and the stored selection are validated strictly.
- Columns containing `hash`, `salt` or `credential` are never encoded.

## [0.1.0] - 2026-10-05

### Added
- QR code button in the form of every asset type, including custom assets.
- Dialog with the QR code, the encoded text, PNG download and print.
- Configurable text template with placeholders, globally and per asset type;
  foreign keys are resolved to their names.
- Translations: German, English, Spanish, French, Italian.

[Unreleased]: https://github.com/mbrigl/glpi-assetqr/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/mbrigl/glpi-assetqr/compare/v0.1.0...v1.0.0
[0.1.0]: https://github.com/mbrigl/glpi-assetqr/releases/tag/v0.1.0
