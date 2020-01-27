<div id="navbar">
  <div class="container-fluid">
    <div class="navbar-group">
      <div class="navbar-element navbar-element-wide">
        <div class="navbar-title">
          <a href="<?php echo $this->url( '/index.php' ) ?>"><?php echo $siteName ?></a>
        </div>
      </div>
      <div id="navbar-element-collapse" aria-expanded="false" class="navbar-element collapse">
        <div class="navbar-sub-group">
          <div class="navbar-sub-element navbar-sub-element-wide">
            <div class="navbar-brand-img"></div>
            <div class="navbar-brand-name">WebIssues <?php echo WI_VERSION ?></div>
          </div>
          <div class="navbar-sub-element">
            <a type="button" title="<?php echo $this->t( 'title.AboutWebIssues' ) ?>" href="<?php echo $this->url( '/client/about.php' ) ?>" class="btn btn-info">
              <span aria-hidden="true" class="fa fa-info-circle"></span>
            </a>
            <a type="button" title="<?php echo $this->t( 'title.WebIssuesGuide' ) ?>" href="<?php echo WI_GUIDE_URL ?>" target="_blank" class="btn btn-info">
              <span aria-hidden="true" class="fa fa-question-circle"></span>
            </a>
          </div>
        </div>
      </div>
      <div id="navbar-element-toggle" class="navbar-element">
        <button id="toggle-button" type="button" title="<?php echo $this->t( 'cmd.ToggleNavigation' ) ?>" class="btn btn-default"><span aria-hidden="true" class="fa fa-bars"></span></button>
      </div>
    </div>
  </div>
</div>
