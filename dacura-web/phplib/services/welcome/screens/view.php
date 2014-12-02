<div id='dashboard-container'>
        <div id='dashboard-menu'>
                <UL class='dashboard-menu'><li class='dashboard-menu-selected'>Seshat &amp; Dacura</li></UL>
                <UL class='dashboard-menu'><li>Together at last</li></UL>
        </div>
        <div id='dashboard-content'>
                                        <div class="dacura-welcome">
                                        Logged in as <?= $params['user'] ?>

                                        <p>This is the home page for the Seshat project on the Dacura Platform. As more tools are developed, they will appear on this screen.
                                        Please note that this page, and all of the seshat data, is private and access is limited to Seshat administrators.
                                        In order to use the tools above, you need to have a Seshat Administrator Role.</p>
                                        <p>In order to use the Page Validator Tool, you need to drag this <a href="javascript:(function(){s=document.createElement('script');s.src='http://dacura.scss.tcd.ie/admin/rest/seshat/0/scraper/grabscript';document.body.appendChild(s);})();">Validator Bookmarklet Link</a>  to your browser's bookmark bar</p>
                                        </div>
                <div id='dashboard-tasks'>
                        <h4>Available Dacura tools</h4>
                        <div class="dacura-dashboard-panel-container">
                                <div class="dacura-dashboard-panel" id="management-panel">
                                        <a href='<?=$service->get_service_url("scraper")?>'>
                                                <div class='dacura-dashboard-button' id='dacura-users-button' title="Export Data">
                                                        <img class='dacura-button-img' src="<?=$service->url("image", "seshat-squatting.gif")?>">
                                                        <div class="dacura-button-title">Export Seshat Data</div>
                                                </div>
                                        </a>
                                </div>
                                <hr style='clear: both'>
                        </div>
                </div>
        </div>
</div>

