# WebIssues

**WebIssues** is an open source, multi-platform system for issue tracking and team collaboration. It can be used to store, share and track issues with various attributes, description, comments and file attachments. It is easy to install and use but has many capabilities and is highly customizable.

Website: https://webissues.mimec.org

## Prototype version :construction:

Note that this is the prototype of version 2.0 of WebIssues. It is currently under development. Please do not use it for production purposes.

The latest stable version of the WebIssues Server can be found [here](https://github.com/mimecorg/webissues-server). The WebIssues Desktop Client can be found [here](https://github.com/mimecorg/webissues-client).

## Requirements

* Web server with PHP version 5.6 or newer

* The following PHP extensions:
  * mbstring (required)
  * mysqli (required when using MySQL database)
  * pgsql (required when using PostgreSQL database)
  * com_dotnet (required when using SQL Server database)

* One of the following database servers:
  * MySQL (version 5.0.15 or newer)
  * PostgreSQL (version 8.0 or newer)
  * Microsoft SQL Server (version 2005 or newer)

## Installation

Quick steps:

1. Copy the entire WebIssues server package to the root directory of the web server.

2. Create a database and a user account with sufficient privileges to create tables in that database.

3. Make sure that the `data/` subdirectory is writable for the web server.

4. Point your browser to the URL of the WebIssues server and follow the instructions.

For more information, please refer to the [WebIssues Manual](http://doc.mimec.org/webissues/1.1/en/).

## Development

In addition to the above requirements, you will also need Node.js and npm to build and run WebIssues from the source code.

Use the following command to build the static JavaScript and CSS assets required to run WebIssues:

```
npm run build
```

Use the following command to start the development server:

```
npm run dev
```

In order to use the development server instead of the static assets, create a file called `data/site.ini` with the following content:

```
[default]
dev_mode = on
```

## Credits

Maintainer: Michał Męciński (https://www.mimec.org)

Contributors:
 * Francine Lai Doo Woo
 * Patrick Matthäi
 * Filipe Azevedo
 * Yvan Rodrigues

License: Affero GPL v3.0

Copyrights:
 * (C) 2006 Michał Męciński
 * (C) 2007-2017 WebIssues Team
