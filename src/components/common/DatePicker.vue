<!--
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
-->

<template>
  <div v-bind:class="[ 'input-group', className ]">
    <input ref="input" type="text" class="form-control" autocomplete="off"
           v-bind:id="id" v-bind:value="value" v-bind:maxlength="maxlength"
           v-on:input="valueChanged" v-on:keydown="keyDown" v-on:blur="close">
    <span v-bind:class="[ 'input-group-btn', { open } ]">
      <button class="btn btn-default" type="button" tabindex="-1" v-on:click="toggle( 'date' )" v-on:mousedown.prevent>
        <span class="fa fa-calendar" aria-hidden="true"></span>
      </button>
      <button v-if="withTime" class="btn btn-default" type="button" tabindex="-1" v-on:click="toggle( 'time' )" v-on:mousedown.prevent>
        <span class="fa fa-clock-o" aria-hidden="true"></span>
      </button>
      <div class="dropdown-menu dropdown-menu-right" v-on:mousedown.prevent>

        <template v-if="mode == 'date'">

          <!-- calendar -->

          <template v-if="selector == null">
            <table class="datepicker datepicker-header">
              <thead>
                <tr>
                  <th class="datepicker-btn" v-on:click="changeMonth( -1 )"><span class="fa fa-chevron-left" aria-hidden="true"></span></th>
                  <th class="datepicker-btn datepicker-wide" v-on:click="openSelector( 'month' )">{{ currentMonthName }} {{ currentYearPadded }}</th>
                  <th class="datepicker-btn" v-on:click="changeMonth( 1 )"><span class="fa fa-chevron-right" aria-hidden="true"></span></th>
                </tr>
              </thead>
            </table>
            <table class="datepicker datepicker-7-cols">
              <thead>
                <tr>
                  <th v-for="day in weekdays">{{ day }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in calendarRows">
                  <td v-for="col in 7" v-bind:class="getDayClass( row, col )" v-on:click="selectDay( row, col )">{{ getDayCell( row, col ) }}</td>
                </tr>
                <tr>
                  <td colspan="2"></td>
                  <td class="datepicker-btn" colspan="3" v-on:click="selectToday()">Today</td>
                  <td colspan="2"></td>
                </tr>
              </tbody>
            </table>
          </template>

          <!-- month selector -->

          <template v-else-if="selector == 'month'">
            <table class="datepicker datepicker-header">
              <thead>
                <tr>
                  <th class="datepicker-btn" v-on:click="changeYear( -1 )"><span class="fa fa-chevron-left" aria-hidden="true"></span></th>
                  <th class="datepicker-btn datepicker-wide" v-on:click="openSelector( 'year' )">{{ currentYearPadded }}</th>
                  <th class="datepicker-btn" v-on:click="changeYear( 1 )"><span class="fa fa-chevron-right" aria-hidden="true"></span></th>
                </tr>
              </thead>
            </table>
            <table class="datepicker datepicker-3-cols">
              <tbody>
                <tr v-for="row in 4">
                  <td v-for="col in 3" class="datepicker-btn" v-on:click="selectMonth( row, col )">{{ getMonthCell( row, col ) }}</td>
                </tr>
              </tbody>
            </table>
          </template>

          <!-- year selector -->

          <template v-else-if="selector == 'year'">
            <table class="datepicker datepicker-header">
              <thead>
                <tr>
                  <th class="datepicker-btn" v-on:click="changeYear( -10 )"><span class="fa fa-chevron-left" aria-hidden="true"></span></th>
                  <th class="datepicker-wide">{{ getYearCell( 1, 1 ) || '0001' }} - {{ getYearCell( 2, 5 ) }}</th>
                  <th class="datepicker-btn" v-on:click="changeYear( 10 )"><span class="fa fa-chevron-right" aria-hidden="true"></span></th>
                </tr>
              </thead>
            </table>
            <table class="datepicker datepicker-5-cols">
              <tbody>
                <tr v-for="row in 2">
                  <td v-for="col in 5" v-bind:class="getYearClass( row, col )" v-on:click="selectYear( row, col )">{{ getYearCell( row, col ) }}</td>
                </tr>
              </tbody>
            </table>
          </template>

        </template>
        <template v-else-if="mode == 'time'">

          <!-- time picker -->

          <template v-if="selector == null">
            <table class="datepicker datepicker-time datepicker-timepicker">
              <tbody>
                <tr>
                  <td class="datepicker-btn" v-on:click="changeTime( 1, 0 )"><span class="fa fa-chevron-up" aria-hidden="true"></span></td>
                  <td class="datepicker-separator"></td>
                  <td class="datepicker-btn" v-on:click="changeTime( 0, 1 )"><span class="fa fa-chevron-up" aria-hidden="true"></span></td>
                  <td></td>
                </tr>
                <tr>
                  <td class="datepicker-btn" v-on:click="openSelector( 'hours' )">{{ hours }}</td>
                  <td class="datepicker-separator">:</td>
                  <td class="datepicker-btn" v-on:click="openSelector( 'minutes' )">{{ minutes }}</td>
                  <td class="datepicker-btn" v-on:click="toggleAmPm()">{{ amPm }}</td>
                </tr>
                <tr>
                  <td class="datepicker-btn" v-on:click="changeTime( -1, 0 )"><span class="fa fa-chevron-down" aria-hidden="true"></span></td>
                  <td class="datepicker-separator"></td>
                  <td class="datepicker-btn" v-on:click="changeTime( 0, -1 )"><span class="fa fa-chevron-down" aria-hidden="true"></span></td>
                  <td></td>
                </tr>
              </tbody>
            </table>
          </template>

          <!-- hours selector -->

          <template v-else-if="selector == 'hours'">
            <table class="datepicker datepicker-time datepicker-4-cols">
              <tbody>
                <tr v-for="row in 3">
                  <td v-for="col in 4" class="datepicker-btn" v-on:click="selectHours( row, col )">{{ getHoursCell( row, col ) }}</td>
                </tr>
              </tbody>
            </table>
          </template>

          <!-- minutes selector -->

          <template v-else-if="selector == 'minutes'">
            <table class="datepicker datepicker-time datepicker-4-cols">
              <tbody>
                <tr v-for="row in 3">
                  <td v-for="col in 4" class="datepicker-btn" v-on:click="selectMinutes( row, col )">{{ getMinutesCell( row, col ) }}</td>
                </tr>
              </tbody>
            </table>
          </template>

        </template>

      </div>
    </span>
  </div>
</template>

<script>
import { KeyCode } from '@/constants'

export default {
  props: {
    id: String,
    value: String,
    maxlength: Number,
    className: String,
    withTime: Boolean,
    withToday: Boolean
  },

  data() {
    return {
      currentValue: this.value,
      currentDate: null,
      selectedDate: null,
      open: false,
      mode: null,
      selector: null,
      weekdays: [ 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa' ],
      months: [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ]
    }
  },

  computed: {
    currentMonth() {
      return this.currentDate.getMonth();
    },
    currentMonthName() {
      return this.months[ this.currentMonth ];
    },
    currentYear() {
      return this.currentDate.getFullYear();
    },
    currentYearPadded() {
      return this.currentYear.toString().padStart( 4, '0' );
    },
    firstWeekDay() {
      return this.currentDate.getDay();
    },
    selectedDay() {
      return this.selectedDate != null ? this.selectedDate.getDate() : null;
    },
    selectedMonth() {
      return this.selectedDate != null ? this.selectedDate.getMonth() : null;
    },
    selectedYear() {
      return this.selectedDate != null ? this.selectedDate.getFullYear() : null;
    },
    hours() {
      return this.selectedDate != null ? ( this.selectedDate.getHours() + 11 ) % 12 + 1 : 12;
    },
    minutes() {
      return this.selectedDate != null ? this.selectedDate.getMinutes().toString().padStart( 2, '0' ) : '00';
    },
    amPm() {
      return this.selectedDate != null && this.selectedDate.getHours() >= 12 ? 'pm' : 'am';
    },
    calendarRows() {
      for ( var i = 6; i > 0; i-- ) {
        if ( this.getDay( i, 1 ) != null )
          return i;
      }
    }
  },

  watch: {
    value( value ) {
      this.currentValue = value;
      this.updateDate();
    }
  },

  methods: {
    focus() {
      this.$refs.input.focus();
    },

    /* changing state */

    toggle( mode ) {
      if ( this.open && this.mode == mode ) {
        this.close();
      } else {
        this.open = true;
        this.mode = mode;
        this.selector = null;
        if ( mode == 'date' ) {
          if ( this.selectedDate ) {
            this.currentDate = this.createDate( this.selectedYear, this.selectedMonth, 1 );
          } else {
            const now = new Date();
            this.currentDate = this.createDate( now.getFullYear(), now.getMonth(), 1 );
          }
        }
        this.$refs.input.focus();
      }
    },
    openSelector( selector ) {
      this.selector = selector;
    },
    close() {
      this.open = false;
      this.mode = null;
      this.selector = null;
    },

    /* manipulating date */

    changeMonth( diff ) {
      const newDate = this.createDate( this.currentYear, this.currentMonth + diff, 1 );
      if ( newDate.getFullYear() >= 1 && newDate.getFullYear() <= 9999 )
        this.currentDate = newDate;
    },
    changeYear( diff ) {
      const newYear = Math.max( 1, Math.min( this.currentYear + diff, 9999 ) );
      this.currentDate = this.createDate( newYear, 0, 1 );
    },
    selectDay( row, col ) {
      const day = this.getDay( row, col );
      if ( day != null ) {
        if ( this.withTime && this.selectedDate != null )
          this.selectedDate = this.createDate( this.currentYear, this.currentMonth, day, this.selectedDate.getHours(), this.selectedDate.getMinutes() );
        else
          this.selectedDate = this.createDate( this.currentYear, this.currentMonth, day );
        this.updateValue();
        this.close();
      }
    },
    selectMonth( row, col ) {
      const month = this.getMonth( row, col );
      this.currentDate = this.createDate( this.currentYear, month, 1 );
      this.selector = null;
    },
    selectYear( row, col ) {
      const year = this.getYear( row, col );
      if ( year != null ) {
        this.currentDate = this.createDate( year, 0, 1 );
        this.selector = 'month';
      }
    },
    selectToday() {
      const now = new Date();
      if ( this.withTime )
        this.selectedDate = this.createDate( now.getFullYear(), now.getMonth(), now.getDate(), now.getHours(), now.getMinutes() );
      else
        this.selectedDate = this.createDate( now.getFullYear(), now.getMonth(), now.getDate() );
      this.updateValue();
      this.close();
    },

    /* displaying date */

    getDay( row, col ) {
      const day = ( row - 1 ) * 7 + col - this.firstWeekDay;
      if ( day >= 1 && this.createDate( this.currentYear, this.currentMonth, day ) < this.createDate( this.currentYear, this.currentMonth + 1, 1 ) )
        return day;
      else
        return null;
    },
    getDayCell( row, col ) {
      return this.getDay( row, col );
    },
    getDayClass( row, col ) {
      const day = this.getDay( row, col );
      return {
        'datepicker-btn': day != null,
        'active': this.selectedYear == this.currentYear && this.selectedMonth == this.currentMonth && this.selectedDay == day
      };
    },
    getMonth( row, col ) {
      return ( row - 1 ) * 3 + col - 1;
    },
    getMonthCell( row, col ) {
      return this.months[ this.getMonth( row, col ) ];
    },
    getYear( row, col ) {
      const year = Math.floor( this.currentYear / 10 ) * 10 + ( row - 1 ) * 5 + col - 1;
      if ( year >= 1 )
        return year;
      else
        return null;
    },
    getYearCell( row, col ) {
      const year = this.getYear( row, col );
      if ( year != null )
        return year.toString().padStart( 4, '0' );
      else
        return null;
    },
    getYearClass( row, col ) {
      const year = this.getYear( row, col );
      return {
        'datepicker-btn': year != null
      };
    },

    /* manipulating time */

    changeTime( hourDiff, minDiff ) {
      let newDate;
      if ( this.selectedDate != null ) {
        newDate = this.createDate( this.selectedYear, this.selectedMonth, this.selectedDay, this.selectedDate.getHours() + hourDiff, this.selectedDate.getMinutes() + minDiff );
      } else {
        const now = new Date();
        newDate = this.createDate( now.getFullYear(), now.getMonth(), now.getDate(), hourDiff, minDiff );
      }
      if ( newDate.getFullYear() >= 1 && newDate.getFullYear() <= 9999 ) {
        this.selectedDate = newDate;
        this.updateValue();
      }
    },
    toggleAmPm() {
      if ( this.selectedDate != null && this.selectedDate.getHours() >= 12 )
        this.changeTime( -12, 0 );
      else
        this.changeTime( 12, 0 );
    },
    selectHours( row, col ) {
      const hours = this.getHours( row, col );
      if ( this.selectedDate != null ) {
        this.selectedDate = this.createDate( this.selectedYear, this.selectedMonth, this.selectedDay, hours, this.selectedDate.getMinutes() );
      } else {
        const now = new Date();
        this.selectedDate = this.createDate( now.getFullYear(), now.getMonth(), now.getDate(), hours, 0 );
      }
      this.updateValue();
      this.selector = null;
    },
    selectMinutes( row, col ) {
      const minutes = this.getMinutes( row, col );
      if ( this.selectedDate != null ) {
        this.selectedDate = this.createDate( this.selectedYear, this.selectedMonth, this.selectedDay, this.selectedDate.getHours(), minutes );
      } else {
        const now = new Date();
        this.selectedDate = this.createDate( now.getFullYear(), now.getMonth(), now.getDate(), 0, minutes );
      }
      this.updateValue();
      this.selector = null;
    },

    /* displaying time */

    getHours( row, col ) {
      return ( row - 1 ) * 4 + col;
    },
    getHoursCell( row, col ) {
      return this.getHours( row, col );
    },
    getMinutes( row, col ) {
      return ( ( row - 1 ) * 4 + col - 1 ) * 5;
    },
    getMinutesCell( row, col ) {
      return this.getMinutes( row, col ).toString().padStart( 2, '0' );
    },

    /* change the value of the input control based on selected date */
    updateValue() {
      let value = '' + ( this.selectedMonth + 1 ) + '/' + this.selectedDay + '/' + this.selectedYear.toString().padStart( 4, '0' );
      if ( this.withTime )
        value += ' ' + this.hours + ':' + this.minutes + ' ' + this.amPm;
      this.setValue( value );
    },

    /* update selected date based on user input */
    updateDate() {
      const date = this.parseDate( this.currentValue );
      if ( date ) {
        this.selectedDate = date;
        this.currentDate = this.createDate( date.getFullYear(), date.getMonth(), 1 );
      }
    },

    setValue( value ) {
      this.currentValue = value;
      this.$refs.input.value = value;
      this.$emit( 'input', value );
    },

    valueChanged( e ) {
      this.setValue( e.target.value );
      this.updateDate();
    },

    keyDown( e ) {
      if ( e.keyCode == KeyCode.Down || e.keyCode == KeyCode.F4 ) {
        if ( !this.open )
          this.toggle( 'date' );
      } else if ( e.keyCode == KeyCode.Esc ) {
        this.close();
      }
    },

    /* create Date based on user input */
    parseDate( value ) {
      if ( value != null ) {
        let parts;
        if ( this.withTime )
          parts = /^\s*(\d\d?)\/(\d\d?)\/(\d\d\d\d)(?:\s+(\d\d?):(\d\d?)\s*([ap]m))?\s*$/i.exec( value );
        else
          parts = /^\s*(\d\d?)\/(\d\d?)\/(\d\d\d\d)\s*$/.exec( value );
        if ( parts ) {
          const month = Number( parts[ 1 ] );
          const day = Number( parts[ 2 ] );
          const year = Number( parts[ 3 ] );
          const date = this.createDate( year, month - 1, day );
          if ( year != 0 && date.getFullYear() == year && date.getMonth() == month - 1 && date.getDate( day ) == day ) {
            if ( this.withTime && parts[ 4 ] != null ) {
              let hours = Number( parts[ 4 ] );
              const minutes = Number( parts[ 5 ] );
              if ( hours >= 1 && hours <= 12 && minutes <= 60 ) {
                if ( hours == 12 )
                  hours = 0;
                if ( parts[ 6 ].toLowerCase() == 'pm' )
                  hours += 12;
                date.setHours( hours, minutes, 0, 0 );
                return date;
              }
            } else {
              return date;
            }
          }
        }
      }
      return null;
    },

    /* create Date object correctly handling years 1 - 99 */
    createDate( year, month, day, hours = 0, minutes = 0 ) {
      const date = new Date();
      date.setFullYear( year, month, day );
      date.setHours( hours, minutes, 0, 0 );
      return date;
    }
  },

  mounted() {
    const date = this.parseDate( this.value );
    if ( date )
      this.selectedDate = date;
  }
}
</script>

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

.datepicker {
  width: 280px;
  margin: 0 5px;

  td, th {
    padding: 7px 5px;
    text-align: center;
    border-radius: @border-radius-base;
  }

  .datepicker-btn:hover {
    background-color: @dropdown-link-hover-bg;
    cursor: pointer;
  }

  .datepicker-btn.active {
    color: @dropdown-link-active-color;
    background-color: @dropdown-link-active-bg;
  }

  .dropdown-menu & .fa {
    color: @text-color;
  }
}

.datepicker-time {
  width: 150px;
}

.datepicker-header {
  th {
    width: 100% / 7;

    &.datepicker-wide {
      width: 100% * 5 / 7;
    }
  }
}

.datepicker-timepicker {
  td {
    width: 30%;

    &.datepicker-separator {
      width: 10%;
    }
  }
}

.datepicker-n-cols( @cols ) {
  .datepicker-@{cols}-cols {
    th, td {
      width: 100% / @cols;
    }
  }
}

.datepicker-n-cols( 3 );
.datepicker-n-cols( 4 );
.datepicker-n-cols( 5 );
.datepicker-n-cols( 6 );
.datepicker-n-cols( 7 );

</style>
