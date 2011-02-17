server {
	
	listen 192.168.75.140;

        log_format squishjs_format '$time_local $remote_addr $status $upstream_cache_status '
	              '$bytes_sent $gzip_ratio $host "$request_uri"';

        log_format squishjs_format_mini '$time_local $remote_addr $status $upstream_cache_status '
	              '$bytes_sent $gzip_ratio $host';


        server_name  localdev.squishjs.com;
        access_log /tmp/squishjs-access.log squishjs_format;
        error_log /tmp/squishjs-error.log;
        
	fastcgi_cache jscache;
	fastcgi_cache_valid 200 302 1m;  # in production this should be much longer
	fastcgi_cache_valid 400 1m;
	fastcgi_cache_key $scheme$http_host$request_uri;

        location / {
	    root /var/web-projects/squishjs/js-shrink;
	    index index.php;
            
	    # rewrite so everything goes to index.php
	    rewrite ^(.+)$ /index.php last; break;
        }
        
        gzip_types text/javascript;
        gzip_min_length 300; # minimum of 300 bytes
        
        expires max;

    location ~ \.php$ {
        
	fastcgi_split_path_info ^(.+\.php)(.*)$;
        fastcgi_pass   unix:/tmp/php-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  /var/web-projects/squishjs/js-shrink$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_intercept_errors        on;
        fastcgi_ignore_client_abort     off;
        fastcgi_connect_timeout 60;
        fastcgi_send_timeout 180;
        fastcgi_read_timeout 180;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
    }	
}
