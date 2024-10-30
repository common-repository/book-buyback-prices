// jQuery('#bbbp-search-form').submit(ajaxSubmit);


function bbbp_ajaxSubmit() {
	if (typeof event !== 'undefined') {
		event.preventDefault();
	}
    
    if(jQuery('#bbbp_isbn_input').val()=='') {
    	jQuery("#bbbpResults").html("Please enter an ISBN before searching.");
    	return false;
    }
    jQuery("#bbbpResults").html("<img src='"+bbbp_spinner_location+"'>");
    
	grecaptcha.ready(function () {
		grecaptcha.execute(BBBP_GOOGLE_RECAPTCHA_SITE_KEY, { action: 'submit' }).then(function (token) {
			var recaptchaResponse = document.getElementById('recaptchaResponse');
			recaptchaResponse.value = token;
			bbbp_ajaxSubmitForm();
		});
	});
	
	
}
function bbbp_ajaxSubmitForm() {

	var bbbpSearchForm = jQuery('#bbbp-search-form').serializeArray();
	
    jQuery.ajax({
        url : ajax_url, // Here goes our WordPress AJAX endpoint.
        type : 'post',
        data : bbbpSearchForm,
        dataType: 'json',
        statusCode: {
			 403: function (xhr) {
				 console.log('403 response');
				 jQuery("#bbbpResults").html( "Unauthorized access (403 error).  Try reloading the page.");
			 }
		 },
        success : function( response ) {
        	// var myObj = jQuery.parseJSON(response);
        	//var myObj = JSON.parse(response);
        	/*
        	jQuery("#bbbpResults").html('');
        	for (var i=0; i<myObj.length; i++) {
                console.log(myObj[i]);
                jQuery("#bbbpResults").append('check console');
            }
            */
			jQuery("#bbbpResults").html('');
            if(response.success==true) {
            	console.log('success = true!');
            	//console.log('response.results.length = '+response.results.length);
            	//jQuery("#bbbpResults").append('<ul class="bbbpResultsUL">');
            	var html = '';
            	if(response.book) {
            		html += '<div class="bbbp_book">';
            			html += '<img src="'+response.book.medium_image+'" class="bbbp_book_image">';
            			html += '<div class="bbbp_book_line bbbp_book_title">';
            				html += response.book.title;
            			html += '</div>';
            			html += '<div class="bbbp_book_line bbbp_book_author">';
            				html += '<span class="bbbp_book_line_label">Author</span>';
            				html += '<span class="bbbp_book_line_value">'+response.book.author+'</span>';
            			html += '</div>';
            			html += '<div class="bbbp_book_line bbbp_book_publisher">';
            				html += '<span class="bbbp_book_line_label">Publisher</span>';
            				html += '<span class="bbbp_book_line_value">'+response.book.publisher+'</span>';
            			html += '</div>';
            			html += '<div class="bbbp_book_line bbbp_book_isbn10">';
            				html += '<span class="bbbp_book_line_label">ISBN-10</span>';
            				html += '<span class="bbbp_book_line_value">'+response.book.isbn10+'</span>';
            			html += '</div>';
            			html += '<div class="bbbp_book_line bbbp_book_isbn13">';
            				html += '<span class="bbbp_book_line_label">ISBN-13</span>';
            				html += '<span class="bbbp_book_line_value">'+response.book.isbn13+'</span>';
            			html += '</div>';
            		html += '</div>';
            	}
            	html += '<ul class="bbbpResultsUL">';
            	var count_shown = 0;
            	if(response.results) {
					for (var i=0; i<response.results.length; i++) {
						// jQuery("#bbbpResults").append('Response: '+response.results[i]['VendorName']+"<br>");
						//jQuery("#bbbpResults").append('Response of something<br>');
						if(response.results[i].Price>0) {
							count_shown++;
							
							var url = response.results[i].affiliate_url;

							var id = response.results[i].VendorId;
							if(bbbpAffLinks[id]) {
								url = bbbpAffLinks[id];
							}
							
							html += '<li>';
								html += '<a class="bbbp_link" href="'+url+'" rel="nofollow" target="_blank">';
									html += '<img class="bbbp_logo" src="'+bbbp_logos_directory+response.results[i].logo_filename+'">';
									html += '<div class="bbbp_vendorName">';
										html += response.results[i].VendorName;
									html += '</div>';
									html += '<div class="bbbp_price">';
										html += '$'+response.results[i].Price.toFixed(2);
									html += '</div>';
								html += '</a>';
								/*
								html += 'VendorName: '+response.results[i].VendorName;
								html += '<br>Price: '+response.results[i].Price;
								html += '<br>UpdatedOn: '+response.results[i].UpdatedOn;
								html += '<br>VendorId: '+response.results[i].VendorId;
								html += '<br>affiliate_url: '+response.results[i].affiliate_url;
								html += '<br>logo_filename: '+response.results[i].logo_filename;
								*/
					
							html += '</li>';
						}
					}
				}
            	html += '</ul>';
            	if(count_shown==0) {
            		html += "Sorry, no results found.";
            	}
				jQuery("#bbbpResults").append(html);
            	
            	// jQuery("#bbbpResults").append(html);
            } else {
            	for (var i=0; i<response.errors.length; i++) {
            		jQuery("#bbbpResults").append('Error: '+response.errors[i]+"<br>");
            	}
            	/*
            	for (var i=0; i<response.error.length; i++) {
            		jQuery("#bbbpResults").append('Error: '+response.error[i]+"<br>");
            	}
            	*/
            }
            //jQuery("#bbbpResults").html(response);
            console.log('response:');
            console.log(response);
        },
        fail : function( err ) {
            jQuery("#bbbpResults").html( "There was an error: " + err );
        }
    });
     
    // This return prevents the submit event to refresh the page.
    return false;
    
}

