humhub.module('modhelper', function(module, require, $) {
	var init= function () {
		this.run();
	}

	var run= function () {
		var d= this.config.dep;
		for (var x=0; x<d.length; x++) {
			var src= $(d[x][0]);
			src.data("modhelper.tests", d[x][1]);
			src.on("change", function () {
				change(this);
			});
		}
	};

	var change= function (src) {
		var tests= jQuery.data(src, "modhelper.tests");
		for (var x=0; x<tests.length; x++) {
			var t= tests[x];
			if (t[0] == "checked") {
				$(t[2]).css("display", src[t[0]] == t[1] ? "block" : "none");
			} else if (t[0] == "func") {
				var f= modhelper_func[t[1]];
				var v= f(src);
				$(t[2]).val(v);
			}
		}
	};

	module.export({
		init: init,
		run: run,
		change: change
	});
});
