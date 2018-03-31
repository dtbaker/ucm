var moving;
var adminApp = {
    init: function () {
        $('.menu-btn').on('click', adminApp.menuAction);
        $('.menu-back, .info-btn').on('click', adminApp.hideMenu);
        $('.mobile-menu').on('click', adminApp.mobileMenu);
        $('.select-all').on('change', adminApp.selectAll);
        $('ul.menu li.dropdown').on('click', adminApp.showSub);
    },
    menuAction: function () {
        moving = true;
        var self = $(this);
        var wrap = $('#wrapper');
        var content = $('#content');
        var sub = $('nav ul.menu li.active ul');
        if (self.hasClass('open')) {
            self.removeClass('open');
            wrap.removeClass('expand');
            sub.slideUp(200);
        } else {
            self.addClass('open');
            wrap.addClass('expand');
            sub.slideDown(200);
        }
        if ($('.charts').length) {
            setInterval(function () {
                if (moving) {
                    update_charts();
                }
            }, 200);
            content.on('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend',
                    function () {
                        moving = false;
                    });
        }
    },
    hideMenu: function () {
        $('.menu-btn').click();
    },
    mobileMenu: function () {
        var menu = $('nav ul.menu');
        menu.slideToggle(200);
    },
    selectAll: function (e) {
        var self = $(this);
        var table = $(e.target).parents('table').find('tbody');
        if (self.is(':checked')) {
            table.find('.prettycheckbox a').each(function () {
                if (!$(this).hasClass('checked')) {
                    $(this).click();
                }
            });
        } else {
            table.find('.prettycheckbox a').each(function () {
                if ($(this).hasClass('checked')) {
                    $(this).click();
                }
            });
        }
    },
    showSub: function () {
        var self = $(this);
        var sub = self.find('ul');
        if (self.hasClass('drop')) {
            self.removeClass('drop');
            sub.stop().slideUp(200);
        } else {
            self.addClass('drop');
            sub.stop().slideDown(200);
        }
    }
};

$(document).ready(adminApp.init);