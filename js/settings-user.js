/**
 * ownCloud - Audio Player
 *
 * @author Marcel Scherello
 * @copyright 
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$(document).ready(function() {

	$('#cyrillic_user').on('click', function() {
		if ($('#cyrillic_user').prop('checked')) {
			var user_value = 'checked';
		}
		else {
			var user_value = '';
		}
    		$.ajax({ 
				type : 'GET',
				url : OC.generateUrl('apps/audioplayer/setvalue'),
				data : {'type': 'cyrillic',
						'value': user_value},
				success : function(ajax_data) {
					$('#notification').text('saved');
					$('#notification').slideDown();
					window.setTimeout(function(){$('#notification').slideUp();}, 3000);	
				}
			});
	});

});