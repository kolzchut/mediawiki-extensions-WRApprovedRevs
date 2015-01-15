( function ( mw, $ ) {
    /* global mediaWiki */
    "use strict";
		var $form, page, cookieName, cookieVal, isSearchPage;

		// Read filter toggle cookie
		cookieName = mw.config.get( 'wgCookiePrefix' ) + '_holoCategoryFilter';
		mw.loader.using( 'jquery.cookie', function() {
			cookieVal = ( $.cookie( cookieName ) === 'true' );
			//mw.log( 'cookieVal on load: ' + cookieVal );
		});

		$form = $( 'form.bodySearch, form#powersearch, form#searchform, form#search' );
		
		page = mw.config.get( 'wgCanonicalSpecialPageName' );
		if( page !== null && ( page === 'Search' || page === 'SphinxSearch' ) ) {
			isSearchPage = true;
		}

		function switchSearchEngine( toEngine, $form ) {
			var searchPageName, searchPageUrl;
			switch( toEngine ) {
				case 'sphinx':	searchPageName = 'Special:SphinxSearch'; break;
				case 'default': searchPageName = 'Special:Search'; break;
			}

			// Point all search forms to relevant search engine:
			searchPageUrl = mw.config.get( 'wgArticlePath' ).replace( '$1', searchPageName );
			$form.attr( 'action', searchPageUrl );
			//Change input name to fit search engine's expectations
			$form.find( 'input[name="' + (toEngine==='sphinx'?'search':'sphinxsearch') + '"]' ).attr( 'name', (toEngine==='sphinx'?toEngine:'') + 'search' );
		}
		
		function checkFilterToggle() {
			var holoCategoryFilterToggle = $('#holoCategoryFilter').is(':checked');
			//mw.log( 'holoCategoryFilterToggle: ' + holoCategoryFilterToggle );
			if( cookieVal !== holoCategoryFilterToggle ) {
				cookieVal = holoCategoryFilterToggle;
				$.cookie( cookieName, cookieVal, { expires: 365, path: '/' } );
			}
			//mw.log( 'cookieVal on search page submit: ' + cookieVal );			
		}

		// Add filter toggle on search pages
		if( isSearchPage  ) {
			var $searchPageForm, $searchBtn, $catFilterToggle, $catFilterToggleLabel;

			$searchPageForm = $( '#content form#search, #content form#powersearch' );
			$searchBtn = $searchPageForm.find( ':submit' );
			$catFilterToggle = $('<input/>',{type:'checkbox',id:'holoCategoryFilter',checked: cookieVal});
			$catFilterToggleLabel = $('<label>',{for:'holoCategoryFilter',id:'holoCategoryFilterLabel'}).text( 'חיפוש בערכי מיזם זכויות ניצולי שואה בלבד' );
			$searchBtn.after( '<br /> ', $catFilterToggle, $catFilterToggleLabel );
		}

		$form.submit( function() {
			if( isSearchPage ) {
				checkFilterToggle();	
			}
			switchSearchEngine( cookieVal ? 'sphinx' : 'default', $form );

			if( cookieVal === true ) {
				var $catFilterInput = $('<input/>',{type:'hidden',name:'cat',value:'3324'});
				// Set a category filter
				$form.append( $catFilterInput );
			}
		});

}( mediaWiki, jQuery ) );
