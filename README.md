# WebIssues

The official WebIssues website is https://webissues.mimec.org.

The documentation for users and administrators can be found at https://doc.mimec.org/webissues-guide/.

## Requirements

* PHP version 5.6 or newer
* MySQL, PostgreSQL or SQL Server database

## Installation

In order to install WebIssues on your server, download the pre-built WebIssues server package unless you are planning to build WebIssues yourself.

Quick steps:

1. Copy the entire WebIssues server package to the root directory of the web server.

    **NOTE:** If you are updating an existing installation of WebIssues server, make sure that you don't delete the `data/` subdirectory.

2. Create a database and a database user with privileges for accessing and modifying it.

3. Point your browser to the URL of the WebIssues server and follow the instructions.

For more information, please refer to the [WebIssues Guide](https://doc.mimec.org/webissues-guide/).

## Updating

You can find information related to updating the WebIssues server in the [Updating WebIssues](https://doc.mimec.org/webissues-guide/system-administration.html#updating-webissues) section of the WebIssues Guide.

## Development

Download the source code or clone it from the git repository and run the following commands:

```bash
npm install
composer install
```

In addition to the requirements listed above, you will also need Node.js version 8 or newer.

To run the development server, use the following command:

```bash
npm run dev:web
```

To use the development server instead of the static assets, create a file called `data/site.ini` with the following content:

```ini
[default]
dev_mode = on
```

Then point your browser to the URL of the WebIssues server.

## Building

Use the following command to build the static JavaScript and CSS assets from the source code:

```bash
npm run build:web
```

## Support

If you have problems related to installing, configuring and using WebIssues, or some other questions, please visit the [Support forum](https://webissues.mimec.org/forum/general) on the WebIssues website.

Ideas and suggestions for improvements can be submitted using the [Feature requests forum](https://webissues.mimec.org/forum/tracker) on the WebIssues website.

Use the [Issues tracker](https://github.com/mimecorg/webissues/issues) on GitHub to submit bugs. Please include the WebIssues version, information about your environment and detailed steps and symptoms of the problem.

## Translations

If you would like to help in translating WebIssues to your language, please join the [Crowdin project](https://crowdin.com/project/webissues).
