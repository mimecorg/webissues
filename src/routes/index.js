/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2017 WebIssues Team
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
**************************************************************************/

import routeGlobal from '@/routes/global'
import routeIssues from '@/routes/issues'
import routeProjects from '@/routes/projects'
import routeTypes from '@/routes/types'
import routeUsers from '@/routes/users'
import routeAlerts from '@/routes/alerts'
import routeSettings from '@/routes/settings'

export default function registerRoutes( router, i18n, ajax, store, formatter ) {
  function routeAll( route ) {
    routeGlobal( route, ajax, store );
    routeIssues( route, i18n, ajax, store, formatter );
    routeProjects( route, ajax, store );
    routeTypes( route, ajax, store );
    routeUsers( route, ajax, store );
    routeAlerts( route, ajax, store );
    routeSettings( route, ajax, store );
  }

  router.register( routeAll );

  if ( process.env.NODE_ENV != 'production' && module.hot != null ) {
    module.hot.accept( [ '@/routes/global', '@/routes/issues', '@/routes/projects', '@/routes/types', '@/routes/users', '@/routes/alerts', '@/routes/settings' ], () => {
      router.hotUpdate( routeAll );
    } );
  }
}
