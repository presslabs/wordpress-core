WORDPRESS_VERSION ?= 5.0.1
WORDPRESS_DOWNLOAD_URL ?= https://wordpress.org/wordpress-$(WORDPRESS_VERSION).tar.gz

build: untar
	./hack/patch-wordpress.php

tag:
	cat composer.json | jq '.provide."wordpress/core-implementation" = "'$(shell ./hack/wp-tag.php)'"' > temp && mv temp composer.json
	git add composer.json
	git commit -m "Release for $(shell ./hack/wp-tag.php)"
	git tag -s -m "Release for $(shell ./hack/wp-tag.php)" "$(shell ./hack/wp-tag.php)"
	git reset --hard HEAD~1

untar: dist-clean
	curl -sL -o wordpress.tar.gz $(WORDPRESS_DOWNLOAD_URL)
	tar -zxf wordpress.tar.gz --strip-components=1
	rm wordpress.tar.gz

dist-clean:
	rm -rf *.php license.txt wp-content wp-includes wp-admin readme.html
