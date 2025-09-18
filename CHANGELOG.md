# Changelog

All notable changes to `gsm-modem` will be documented in this file.

## [Unreleased]

## [1.0.0] - 2025-01-18

### Added
- Initial release of Laravel GSM Modem Package
- SMS management with TEXT and PDU mode support
- Call handling functionality (make, answer, hangup)
- USSD command support
- Network and signal strength information retrieval
- SIM card management and PIN unlock
- Serial port communication with configurable parameters
- Event system for incoming messages and calls
- Artisan commands for testing, monitoring, and sending SMS
- Comprehensive test suite
- Full documentation
- Support for Laravel 10, 11, and 12
- PHP 8.3+ support

### Features
- Send, receive, and delete SMS messages
- Make and manage phone calls
- Execute USSD codes
- Monitor signal strength and network status
- Manage SIM card operations
- Listen for incoming messages and calls via event system
- CLI tools for modem interaction

### Supported Hardware
- Huawei E-series modems
- ZTE modems
- Sierra Wireless modems
- Simcom modules (SIM800, SIM900)
- Quectel modules
- Any Hayes AT command compatible modem