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
        renderResult: function($row, item) {
            $row.find('td.icon')
                .css('background-image', 'url(' + OC.imagePath('audioplayer', 'app-dark') + ')')
                .css('opacity', '.4');
            return $row;
        }
    };
    OCA.Search.Audiplayer = Audiplayer;
    OCA.Search.audiplayer = new Audiplayer();
})();