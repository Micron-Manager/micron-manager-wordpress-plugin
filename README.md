# Micron Manager

**Plugin Name:** Micron Manager  
**Version:** 1.0.0  
**Author:** Lorenzo Quinti  
**License:** GPL v2 or later  
**Requires WordPress:** 6.0+  
**Requires PHP:** 8.0+

## Description

Micron Manager is a WordPress plugin that exposes custom REST API endpoints for external applications. It provides a secure and flexible way to manage customer data through REST API endpoints, enabling seamless integration with external systems and applications.

## Features

- Custom REST API endpoints for customer management
- Secure authentication and permission checks
- WordPress native integration
- Comprehensive search and filtering capabilities
- Standards-compliant REST API responses

## Installation

### Manual Installation

1. Download the plugin zip file
2. Log in to your WordPress admin dashboard
3. Navigate to **Plugins** > **Add New**
4. Click **Upload Plugin**
5. Choose the downloaded zip file and click **Install Now**
6. Activate the plugin through the **Plugins** menu in WordPress

### Via WordPress Repository (when available)

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins** > **Add New**
3. Search for "Micron Manager"
4. Click **Install Now** on the Micron Manager plugin
5. Activate the plugin

### Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- Valid WordPress user permissions for API access

## Usage

### API Endpoints

The plugin provides the following REST API endpoints under the `micron-manager/v1` namespace:

#### Customers Endpoint

**GET** `/wp-json/micron-manager/v1/customers`

Retrieve customers with optional filtering and search parameters.

**Query Parameters:**
- `search` - Search across email, first_name, last_name, company, username fields
- `per_page` - Number of results per page (default: 10, max: 100)
- `page` - Page number for pagination
- Additional filtering parameters available

### Authentication

API endpoints require proper WordPress authentication. Ensure your application has appropriate user permissions before making requests.

## Development

### File Structure

```
micron-manager-wordpress-plugin/
├── micron-manager.php                          # Main plugin file
├── uninstall.php                              # Uninstall cleanup
├── includes/
│   └── class-micron-manager-rest-customers-controller.php
├── admin/                                     # Admin interface files
├── languages/                                 # Translation files
└── README.md                                  # This file
```

### Coding Standards

This plugin follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

## FAQ

### Q: How do I access the API endpoints?

A: The API endpoints are available at `/wp-json/micron-manager/v1/` followed by the specific endpoint (e.g., `customers`). You'll need proper WordPress authentication to access them.

### Q: What permissions are required to use the API?

A: The plugin implements WordPress's built-in permission system. Ensure your user account has appropriate capabilities for the operations you want to perform.

### Q: Can I customize the API endpoints?

A: Yes, the plugin is built with WordPress hooks and filters, allowing developers to extend and customize functionality as needed.

### Q: Is this plugin compatible with caching plugins?

A: Yes, the plugin is designed to work with WordPress caching mechanisms. However, API responses are typically not cached to ensure real-time data.

### Q: How do I report bugs or request features?

A: Please visit the [GitHub repository](https://github.com/lorenzoquinti/micron-manager-wordpress-plugin) to report issues or submit feature requests.

## Changelog

### 1.0.0 - 2026-02-10

#### Added
- Initial release
- REST API customers controller
- Custom API endpoints under `micron-manager/v1` namespace
- Comprehensive search and filtering capabilities
- WordPress-native authentication and permissions
- Secure API endpoint implementation

#### Security
- Implemented proper permission checks for all endpoints
- Added input sanitization and validation
- ABSPATH protection for all PHP files

## Support

For support, please:

1. Check this README and FAQ section
2. Review the [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
3. Visit the [GitHub repository](https://github.com/lorenzoquinti/micron-manager-wordpress-plugin) for issues and documentation

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Follow WordPress Coding Standards
4. Submit a pull request with clear description

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Credits

Developed by Micron Manager team.