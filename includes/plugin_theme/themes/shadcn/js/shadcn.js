ucm = ucm || {};
ucm.shadcn = {
	store: function (key, value) {
		try {
			localStorage.setItem(key, value);
		} catch (e) {
		}
	},
	init: function () {
		var root = document.documentElement;
		var $body = $('body');

		// Bare inputs have no classes and the markup is generated all over the app: tag them so
		// the stylesheet (and Bootstrap's own rules) treat them as form controls.
		$('input[type=text], input[type=password], input[type=email], input[type=number], input[type=url], input[type=tel], select, textarea', 'form').not('.form-control, .no-form-control').each(function () {
			$(this).addClass('form-control');
		});
		$('.submit_button').not('.btn').addClass('btn');

		// colour mode
		var syncModeIcon = function () {
			$('#mode-toggle i').attr('class', 'fa ' + (root.getAttribute('data-theme') === 'dark' ? 'fa-sun-o' : 'fa-moon-o'));
		};
		syncModeIcon();
		$('#mode-toggle').on('click', function () {
			var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
			root.setAttribute('data-theme', next);
			ucm.shadcn.store('ucm-theme-mode', next);
			syncModeIcon();
		});

		// sidebar: collapses on desktop, slides over the page on small screens
		var small = function () {
			return window.matchMedia('(max-width: 991px)').matches;
		};
		$('#sidebar-toggle').on('click', function () {
			if (small()) {
				$body.toggleClass('sidebar-open');
			} else {
				var collapsed = root.getAttribute('data-sidebar') === 'collapsed';
				if (collapsed) {
					root.removeAttribute('data-sidebar');
				} else {
					root.setAttribute('data-sidebar', 'collapsed');
				}
				ucm.shadcn.store('ucm-sidebar', collapsed ? 'open' : 'collapsed');
			}
		});
		$('#sidebar-backdrop').on('click', function () {
			$body.removeClass('sidebar-open');
		});
		$('#left').on('click', 'a', function () {
			if (small()) {
				$body.removeClass('sidebar-open');
			}
		});
	}
};
