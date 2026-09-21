all : fst.min.css fst.min.js fst-jquery.min.js fst-jqueryui.min.js
	continue

fst.min.css : fst.css
	uglifycss fst.css > fst.min.css

fst.min.js : fst.js
	uglifyjs fst.js > fst.min.js

fst-jquery.min.js : fst-jquery.js
	uglifyjs fst-jquery.js > fst-jquery.min.js

fst-jqueryui.min.js : fst-jqueryui.js
	uglifyjs fst-jqueryui.js > fst-jqueryui.min.js
