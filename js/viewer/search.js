'use strict';

(function() {
    /**
     * Construct a new FileActions instance
     * @constructs Files
     */
    var Audiplayer = function() {
        this.initialize();
    };
    /**
     * @memberof OCA.Search
     */
    Audiplayer.prototype = {
        initialize: function() {
            OC.Plugins.register('OCA.Search.Core', this);
        },
        attach: function(search) {
            search.setRenderer('audioplayer', this.renderResult);
        },
        renderResult: function(row, item) {
            var element = row instanceof HTMLElement ? row : row[0];
            var icon = element.querySelector('td.icon');
            if (icon) {
                icon.style.backgroundImage = 'url(' + OC.imagePath('audioplayer', 'app-dark') + ')';
                icon.style.opacity = '.4';
            }
            return element;
        }
    };
    OCA.Search.Audiplayer = Audiplayer;
    OCA.Search.audiplayer = new Audiplayer();
})();