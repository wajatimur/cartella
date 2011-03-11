
//loads our page content by getting all modlets and calling their loader
function loadPage() {

	updateSiteStatus("Loading all applets");
	endReq("clearSiteStatus()");

	loadModNav();

	//for some reason, ie throws an error when everything is loaded in the normal order, so I have to reverse it
	if (document.all) {

		loadAllModlets(ge("LeftColumn"),ge("RightColumn"));

		siteSorter = new Sortables([ge("LeftColumn"),ge("RightColumn")], {

				handle: 'div',

				revert: { duration: 250, transition: 'linear' },

				opacity: .25,

				clone: true,

				onComplete: function() {
					saveLayout();
				},

				onStart: function() {
					this.clone.style.width = "350px";
					if (!document.all) this.clone.style.marginLeft = "250px";
				}

			});

	} else {


		siteSorter = new Sortables([ge("LeftColumn"),ge("RightColumn")], {

				revert: { duration: 250, transition: 'linear' },

				handle: 'div',

				opacity: .25,

				clone: true,

				onComplete: function() {
					saveLayout();
				},

				onStart: function() {
					this.clone.style.width = "350px";
					if (!document.all) this.clone.style.marginLeft = "250px";
				}

			});

		loadAllModlets(ge("LeftColumn"),ge("RightColumn"));

	}



}

function loadModNav() {

	showModNav();

	addModNav("New Task","createNewTask()","new.png");
	addModNav("Edit My Profile","location.href = 'index.php?module=accounts&accountId=" + USER_ID + "'","profile.png");
	addModNav("Add Applet To Page","addModlet()","addmodlet.png");

}
