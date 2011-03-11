
//loads our page content by getting all modlets and calling their loader
function loadPage() {

  updateSiteStatus("Loading all applets");
  endReq("clearSiteStatus()");

  loadAllModlets(ge("LeftColumn"),ge("RightColumn"));

  siteSorter = new Sortables([ge("LeftColumn"),ge("RightColumn")], {

        revert: { duration: 250, transition: 'linear' },

        onComplete: function() {
          saveLayout();
        },

        onStart: function() {
          this.clone.style.width = "350px";
          if (!document.all) this.clone.style.marginLeft = "250px";
        }

      });


	showModNav();
	addModlet();

}

