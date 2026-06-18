$(document).ready(function () {
	$('.nx-select-area .nx-select').chosen({
			no_results_text: "Нет элемента",
		});

	$('.nx-select-area .chosen-search input').on('input', function () {

		if($(this).val().length > 2) {
			var parent = $(this).closest('.nx-select-area');
			var value = $(this).val();
			var table = $(parent).data('table');
			var type = $(parent).data('type');
			var idSelect = $(parent).find('select').attr('id');
			var dataSend = {
				mode: 'search',
				string: $(this).val(),
				sessid: BX.bitrix_sessid()
			};
			if(type == 'hlblock') {
				dataSend.type = 'hlblock';
				dataSend.hlblock = table;
			} else if(type == 'user') {
				dataSend.user = 'Y';
			}

			$.ajax({
				url: '/local/components/ip/nx.hlblock.element.selector/ajax.php',
				type: 'POST',
				data: dataSend,
				async: false,
				dataType: "json",
				beforeSend: function(){$('#'+idSelect).empty(); }
			}).done(function (data) {
				if(data.length) {
					$('#'+idSelect).append('<option value="">(все)</option>');
					$.map( data, function( item ) {
						$('#'+idSelect).append('<option value="' + item.ID + '">' + item.NAME + '</option>');
					});
					$('#'+idSelect).trigger("chosen:updated");
					console.log($(parent).find('.chosen-search input'));
					$('#'+idSelect+'_chosen .chosen-search input').val(value);
				}
			});
		}

	})
});