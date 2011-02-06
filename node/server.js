/**
 * Server written in node.js so we can: 
 * 
 * a) handle a lot more concurrent connections and slow hanging connections
 * b) use nginx in the front end to proxy requests to us
 * 
 * How this should work: 
 * 
 * 1. Read the GET data from http.server,
 *    - http://server/squish/?file=http://..../whatever.js&type=(jsmin|uglify)
 * 2. fetch the file from the remote server
 *    - check that the content type is text/javascript
 * 3. uglify it
 *    -
 * 4. return the output w/ squishjs headers
 *
 */
var ugmin = require('./lib/uglify-9baf991'),
    js_parser = ugmin.parser,
    js_processor = ugmin.uglify,
    http = require('http'); 

// fetch https://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.js

var client = http.createClient(443, 'ajax.googleapis.com', true),
    request = client.request('GET', '/ajax/libs/jquery/1.4.4/jquery.js',
    { 'host' : 'ajax.googleapis.com'});

request.end();
request.on('response', function(response) {
    var bodyData;
    
    console.log('STATUS: ' + response.statusCode);
    console.log('HEADERS: ' + JSON.stringify(response.headers));
    response.setEncoding('utf8');
    response.on('data', function (chunk) {
        bodyData += chunk;
    });

    response.on('end', function() {
        var ast = js_parser.parse(bodyData);
        ast = js_processor.ast_mangle(ast);
        ast = js_processor.ast_squeeze(ast);
        console.log(js_processor.gen_code(ast));
    })

});
/*

var ast = js_parser.parse('var y = function(one, two, three) { alert(one + two + three); }');
ast = js_processor.ast_mangle(ast);
ast = js_processor.ast_squeeze(ast);
console.log(js_processor.gen_code(ast));
*/