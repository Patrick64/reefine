(function() {
	var container = document.getElementById('reefine');
	var params = REEFINE_DATA ? getParams(REEFINE_DATA.filter_url) : ''; // current search parameters

	function reefine_ajax_init() {

		// AJAXify paging
		var pageLinks = container.getElementsByTagName('a');
		var i;
		for (i = 0; i < pageLinks.length; i = i + 1) {
			if (/P\d+$/.test(pageLinks[i].href)) {
				pageLinks[i].onclick = function() {
					AJAXPost(this.href + (/\?/.test(this.href) ? '&' : '?') + params + "&ajax_request=1", ajax_success);
					return false;
				};
			}
		}

		// AJAXify filter links
		var filterLinks = document.getElementById('reefine_sidebar').getElementsByTagName('a');
		for (i = 0; i < filterLinks.length; i = i + 1) {
			filterLinks[i].onclick = function() {
				AJAXPost(this.href + (/\?/.test(this.href) ? '&' : '?') + "ajax_request=1", ajax_success);
				params = getParams(this.href);
				return false;
			};
		}

		// AJAXify search/number_range forms
		var filterForms = container.getElementsByTagName('form');
		for (i = 0; i < filterForms.length; i = i + 1) {
			filterForms[i].onsubmit = function() {
				// get parameters
				params = serialize(this);
				var url = this.action;
				AJAXPost(url + (/\?/.test(url) ? '&' : '?') + params + "&ajax_request=1", ajax_success);
				return false;
			};
		}
	}

	
	function getParams(url) {
		var matches = url.match(/\?(.*$)/);
		if (matches && matches.length>1)
			return matches[1];
		else
			return '';
	}
	
	function ajax_success(response) {

		var html = '';
		if (JSON)
			html = JSON.parse(response);
		else
			eval('html=' + response);
		document.getElementById('reefine').innerHTML = html;
		// remove loading css class
		container.className = container.className.replace(/\bloading\b/, '');
		reefine_ajax_init();

	}

	// http://stackoverflow.com/questions/6990729/simple-ajax-form-using-javascript-no-jquery
	function AJAXPost(url, callback) {
		var xmlhttp;
		// set loading anim
		container.className = container.className + " loading";
		// code for IE7+, Firefox, Chrome,Opera,Safari
		if (window.XMLHttpRequest) {
			xmlhttp = new XMLHttpRequest();
		} else {// code for IE6, IE5
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				callback(xmlhttp.responseText);
			}
		};

		xmlhttp.open("GET", url, true);
		xmlhttp.setRequestHeader("content-type", "application/x-www-form-urlencoded");
		// xmlhttp.setRequestHeader("content-length", params.length);
		// xmlhttp.setRequestHeader("connection", "close");

		// xmlhttp.send(params);
		xmlhttp.send(null);
	}
		
	// https://code.google.com/p/form-serialize/
	function serialize(form){if(!form||form.nodeName!=="FORM"){return }var i,j,q=[];for(i=form.elements.length-1;i>=0;i=i-1){if(form.elements[i].name===""){continue}switch(form.elements[i].nodeName){case"INPUT":switch(form.elements[i].type){case"text":case"hidden":case"password":case"button":case"reset":case"submit":q.push(form.elements[i].name+"="+encodeURIComponent(form.elements[i].value));break;case"checkbox":case"radio":if(form.elements[i].checked){q.push(form.elements[i].name+"="+encodeURIComponent(form.elements[i].value))}break;case"file":break}break;case"TEXTAREA":q.push(form.elements[i].name+"="+encodeURIComponent(form.elements[i].value));break;case"SELECT":switch(form.elements[i].type){case"select-one":q.push(form.elements[i].name+"="+encodeURIComponent(form.elements[i].value));break;case"select-multiple":for(j=form.elements[i].options.length-1;j>=0;j=j-1){if(form.elements[i].options[j].selected){q.push(form.elements[i].name+"="+encodeURIComponent(form.elements[i].options[j].value))}}break}break;case"BUTTON":switch(form.elements[i].type){case"reset":case"submit":case"button":q.push(form.elements[i].name+"="+encodeURIComponent(form.elements[i].value));break}break}}return q.join("&")}
		
	reefine_ajax_init();
})();