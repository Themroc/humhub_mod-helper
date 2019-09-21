humhub.module('modhelper', function(module, require, $) {
	var init= function () {
//		console.log('modhelper module activated');
		var c= this.config.dep;

		for (var x=0; x<c.length; x++) {
			var src= $(c[x][0]);
			src.data("modhelper.tests", c[x][1]);
			src.on("change", function () {
				change(this);
			});
		}
	};

	var change= function (src) {
		var tests= jQuery.data(src, "modhelper.tests");

		for (var x=0; x<tests.length; x++) {
			var t= tests[x];
			$(t[2]).css("display", src[t[0]] == t[1] ? "block" : "none");
		}

	};

	module.export({
		init: init,
		change: change
	});
});
