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

var WebIssues = WebIssues || {};

( function ( WebIssues, $ ) {
    WebIssues.classParam = function( node, name ) {
        var classes = node.attr( 'class' ).split( ' ' );
        for ( i in classes ) {
            if ( classes[ i ].substring( 0, name.length + 1 ) == name + '-' )
                return classes[ i ].substring( name.length + 1 );
        }
        return '';
    }

    WebIssues.autofocus = function() {
        var forms = $( 'form:not(.form-inline)' );
        if ( forms.length > 0 ) {
            var wrongInput = [];
            var fieldError = forms.find( '.error' );
            if ( fieldError.length > 0 ) {
                wrongInput = fieldError.prev( ':input' );
                if ( wrongInput.length == 0 )
                    wrongInput = fieldError.prevAll( '.form-field:first' ).children( ':input' );
            }

            var toHighlight = [];
            if  ( wrongInput.length > 0 )
                toHighlight = wrongInput;
            else
                toHighlight = forms.find( ':input:enabled' );

            toHighlight.each( function() {
                if ( $( this ).is( ':text,:password,:radio:checked,:checkbox,select,textarea' ) ) {
                    this.focus();
                    return false;
                }
            } );
        }
    }

    WebIssues.autoalign = function() {
        var info = $( '.info-align' );
        if ( info.length > 0 ) {
            var maxW = 0;
            info.each( function() {
                var w = $( this ).find( 'td' ).first().width();
                if ( maxW < w )
                    maxW = w;
            } );
            info.each( function() {
                $( this ).find( 'td' ).first().css( 'width', Math.ceil( maxW ) );
            } );
        }
    }

    WebIssues.switchClient = function( client, options ) {
        $( '.switch-client' ).click( function() {
            $.cookie( 'wi_client', client, options );
        } );
    }

    WebIssues.expandCookie = function( cookieName, options ) {
        var expandedIds = [];
        var cookieContent = $.cookie( cookieName );
        if ( cookieContent )
            expandedIds = cookieContent.split( '|' );
        $( '.expand' ).show();
        $( '.blank' ).show();
        $( '.collapse' ).hide();
        $( '.child' ).hide();
        for ( i in expandedIds ) {
            if ( expandedIds[ i ].length > 0 ) {
                $( '.child.parent-' + expandedIds[ i ] ).show();
                $( '.parent.parent-' + expandedIds[ i ] + ' .collapse' ).show();
                $( '.parent.parent-' + expandedIds[ i ] + ' .expand' ).hide();
            }
        }
        $( '.expand, .collapse' ).click( function() {
            var id = WebIssues.classParam( $( this ).parents( 'tr' ), 'parent' );
            $( '.child.parent-' + id ).toggle();
            $( this ).hide();
            var result = [];
            var k = 0;
            if ( $( this ).hasClass( 'expand' ) ) {
                $( '.parent.parent-' + id + ' .collapse' ).show();
                var found = false;
                for ( i in expandedIds ) {
                    if ( expandedIds[ i ].length > 0 ) {
                        if ( expandedIds[ i ] == id )
                            found = true;
                        result[ k ] = expandedIds[ i ];
                        k++;
                    }
                }
                if ( !found )
                   result[ k ] = id;
            } else {
                $( '.parent.parent-' + id + ' .expand' ).show();
                for ( i in expandedIds ) {
                    if ( ( expandedIds[ i ].length > 0 ) && ( expandedIds[ i ] != id ) ) {
                        result[ k ] = expandedIds[ i ];
                        k++;
                    }
                }
            }
            expandedIds = result;
            $.cookie( cookieName, expandedIds.join( '|' ), options );
            return false;
        } );
    };

    WebIssues.deparam = function( query ) {
        var params = {};
        var pairs = query.split( '&' );
        for ( var i in pairs ) {
            var parts = pairs[ i ].split( '=' );
            params[ parts[ 0 ] ] = parts[ 1 ];
        }
        return params;
    }

    WebIssues.sortByKey = function( obj ) {
        var keys = [];
        for ( var i in obj )
            keys.push( i );
        keys.sort();
        var sorted = {};
        for ( var i in keys )
            sorted[ keys[ i ] ] = obj[ keys[ i ] ];
        return sorted;
    }

    WebIssues.mergeQueryString = function( url, query ) {
        var params = {};
        var parts = url.split( '?' );
        if ( parts[ 1 ] != undefined )
            params = WebIssues.deparam( parts[ 1 ] );
        $.extend( params, WebIssues.deparam( query ) );
        var result = parts[ 0 ];
        if ( params.length != 0 )
            result += '?' + $.param( WebIssues.sortByKey( params ) );
        return result;
    }

    WebIssues.initSelection = function( commands ) {
        $( '.grid td a:not(.expand):not(.collapse)' ).click( function() {
            var row = $( this ).parents( 'tr' );
            $( '.grid tr' ).removeClass( 'selected' );
            row.addClass( 'selected' );
            for ( i in commands ) {
                var command = commands[ i ];
                var visible = true;
                for ( j in command.conditions ) {
                    if ( !row.hasClass( command.conditions[ j ] ) )
                        visible = false;
                }
                if ( visible ) {
                    $( '#cmd-' + i ).show();
                    var links = $( '#cmd-' + i + ' a' );
                    var url = links.attr( 'href' );
                    if ( command.row != undefined )
                        url = WebIssues.mergeQueryString( url, command.row + '=' + WebIssues.classParam( row, 'row' ) );
                    if ( command.parent != undefined )
                        url = WebIssues.mergeQueryString( url, command.parent + '=' + WebIssues.classParam( row, 'parent' ) );
                    links.attr( 'href', url );
                } else {
                    $( '#cmd-' + i ).hide();
                }
            }
            return false;
        } );
    }

    $( function() {
        WebIssues.autofocus();
        WebIssues.autoalign();
    } );
} )( WebIssues, jQuery );
