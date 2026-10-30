# Oihana PHP - Nginx

[![Oihana PHP Nginx](https://raw.githubusercontent.com/BcommeBois/oihana-php-nginx/main/assets/images/oihana-php-nginx-logo-inline-512x160.png)](https://github.com/BcommeBois/oihana-php-nginx)

PHP toolkit to create, modify, and control [NGINX](https://nginx.org/) configurations and commands programmatically.

Built on top of the [Oihana PHP Commands](https://github.com/BcommeBois/oihana-php-commands/) Library.

[![Latest Version](https://img.shields.io/packagist/v/oihana/php-nginx.svg?style=flat-square)](https://packagist.org/packages/oihana/php-nginx)  
[![Total Downloads](https://img.shields.io/packagist/dt/oihana/php-nginx.svg?style=flat-square)](https://packagist.org/packages/oihana/php-nginx)  
[![License](https://img.shields.io/packagist/l/oihana/php-nginx.svg?style=flat-square)](LICENSE)

## 📦 Installation

> **Requires [PHP 8.4+](https://php.net/releases/)**

Install via [Composer](https://getcomposer.org):

```shell
composer require oihana/php-nginx
```

## 📚 Usage

### Configuration Management

The library provides comprehensive tools for managing Nginx configurations:

- **Path Management**: Handle different Nginx configuration paths
- **Signal Control**: Send signals to Nginx processes
- **Redirect Management**: Create and manage redirect blocks
- **Options Configuration**: Configure Nginx options programmatically

### Enums

- `NginxPath`: Defines standard Nginx configuration paths
- `NginxSignal`: Available Nginx process signals
- `RedirectDirection`: Redirect direction options

### Helpers

- `redirectBlock()`: Generate redirect block configurations
- `nginxStatements()`: Split a configuration into its instructions, comments and layout left out, to compare two files instruction by instruction
- `vhostAcmeRoot()`: Read the directory a vhost serves the ACME challenge from
- `vhostCertificateName()`: Read the name of the certbot certificate a vhost serves

### Options

- `NginxOption`: Individual Nginx option management
- `NginxOptions`: Collection of Nginx options

## 🧪 Testing

To run all tests:

```shell
composer test
```

To measure the coverage, with Xdebug (an HTML report and a Markdown summary land in `build/coverage/`):

```shell
composer coverage:md
```

## 📖 Documentation

For detailed documentation, please refer to the source code and test files in the `src/` and `tests/` directories.

### Project Structure

```
src/oihana/nginx/
├── enums/           # Nginx-related enumerations
├── helpers/         # Helper functions
├── options/         # Configuration options
└── traits/          # Reusable traits
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the [Mozilla Public License 2.0 (MPL-2.0)](https://www.mozilla.org/en-US/MPL/2.0/).

## 🔗 Dependencies

This library is built on top of the **Oihana PHP Framework** and includes the following core dependencies:

### Core Framework Dependencies

- **oihana/php-core**: Core framework functionality
- **oihana/php-reflect**: Reflection utilities
- **oihana/php-enums**: Enumeration support
- **oihana/php-schema**: Schema validation
- **oihana/php-standards**: Coding standards and utilities
- **oihana/php-files**: File system operations
- **oihana/php-system**: System-level operations
- **oihana/php-commands**: Command-line interface framework

### Development Dependencies

- **phpunit/phpunit**: Unit testing framework
- **nunomaduro/collision**: Error reporting for console applications
- **mikey179/vfsstream**: Virtual file system for testing
- **phpdocumentor/shim**: Documentation generation support

All framework dependencies are currently using the `dev-main` branch for development purposes (alpha version).