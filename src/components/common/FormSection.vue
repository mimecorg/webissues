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
  <div class="form-section">
    <div class="form-section-title">
      <div class="form-section-header">{{ title }}</div>
    </div>
    <div class="form-section-buttons">
      <slot/>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    title: String
  }
}
</script>

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

.form-section {
  .group();
  margin-top: 3px;
  margin-bottom: 3px;
}

.form-section-title {
  .element();
  .element-wide();
  vertical-align: bottom;
}

.form-section-header {
  font-weight: bold;
  margin: 7px 0 6px 0;
  font-size: @form-section-font-size;
}

.form-section-buttons {
  .element();
  vertical-align: bottom;
}
</style>
