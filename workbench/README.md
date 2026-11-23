# Workbench

The Workbench provides a local Laravel application for developing, testing, and previewing the **Ellaisys AWS Cognito** package.

It allows contributors to interact with the package in a Laravel environment without creating a separate Laravel application.

## Requirements

Before using the Workbench, ensure that you have:

- PHP version supported by the package.
- Composer.
- Node.js and npm (if working with frontend assets).
- AWS Cognito User Pool and App Client credentials for testing Cognito integration.

## Installation

From the package root directory, install the project dependencies:

```bash
composer install
```

If the Workbench has not yet been scaffolded, you can initialize it using:

```bash
vendor/bin/testbench workbench:install
```

## Configuration

The Workbench uses the package configuration and the `testbench.yaml` file located in the project root.

Review the following configuration areas before running the application:

- **Service Providers:** Ensure the package service provider is registered.
- **Environment Variables:** Configure the AWS Cognito settings required for local development.
- **Database:** Configure the database connection if the development workflow requires database access.
- **Routes and Views:** Configure the Workbench routes and package resources as required.

Do not commit AWS credentials, secrets, or other sensitive environment values to version control.

Refer to the package's main README for the available AWS Cognito configuration options.

## Running the Workbench

Start the local development application from the package root:

```bash
composer run serve
```

This starts the Workbench application and allows you to preview and interact with the package.

## Running Artisan Commands

Package console commands can be executed directly through the Testbench CLI.

For example:

```bash
vendor/bin/testbench list
```

To display the available options for a package command:

```bash
vendor/bin/testbench help cognito:purge
```

To execute a package command:

```bash
vendor/bin/testbench cognito:purge
```

Refer to the command's help output for its supported arguments and options.

### Clean Slate Command

The Clean Slate command allows you to reset AWS Cognito resources used during testing. It removes temporary user pools, clients, groups, and terms created for testing purposes.

To execute the Clean Slate command:

```bash
vendor/bin/testbench cognito:clean-slate
```

You can also specify the user pool ID if needed:

```bash
vendor/bin/testbench cognito:clean-slate --pool-id=your_user_pool_id
```

## Running Tests

Run the PHPUnit test suite from the package root:

```bash
vendor/bin/phpunit
```

To execute a specific test suite:

```bash
vendor/bin/phpunit --testsuite=Unit
```

```bash
vendor/bin/phpunit --testsuite=Feature
```

## Development Notes

- The Workbench is intended for local package development and manual verification.
- Automated tests should be maintained separately under the project's `tests` directory.
- Keep local credentials and environment-specific configuration out of version control.
- Changes to package source files should be verified through the PHPUnit test suite before being committed.

## Additional Resources

- [Laravel Package Development](https://laravel.com/docs/packages)
- [Orchestra Testbench](https://packages.tools/testbench)
- [Orchestra Workbench](https://packages.tools/workbench)
- [AWS Cognito Documentation](https://docs.aws.amazon.com/cognito/)

