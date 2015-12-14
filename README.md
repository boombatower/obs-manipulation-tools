obs-manipulation-tools
======================

Rough scripts/templates used to manipulate rpm spec files for open build service (obs) packages.

These scripts are not production quality, do not come with real documentation, and are not parameterized for general use. If anything they demonstrate techniques and act as templates for doing one-off repetitive manipulation of packages.

examples
--------

- `obsoletes.php` script generated:
```diff
@@ -228,6 +228,7 @@
 Requires:       pcre-devel
 Provides:       pecl
 Provides:       php-devel
+Obsoletes:      php5-devel
 
 %description devel
 PHP is a server-side, cross-platform, HTML embedded scripting language.
@@ -244,6 +245,7 @@
 Requires:       %{name}-zlib = %{version}
 Requires:       php-pear-Archive_Tar
 Provides:       php-pear
+Obsoletes:      php5-pear
 
 %description pear
 PEAR is a code repository for PHP extensions and PHP library code
```

- `convert.php` script generated [submit request](https://build.opensuse.org/request/show/348119):
```diff
@@ -185,12 +185,7 @@
 
 # Clean up unnecessary files
 rm pearrc
-rm %{buildroot}/%{peardir}/.filemap
-rm %{buildroot}/%{peardir}/.lock
-rm -rf %{buildroot}/%{peardir}/.registry
-rm -rf %{buildroot}%{peardir}/.channels
-rm %{buildroot}%{peardir}/.depdb
-rm %{buildroot}%{peardir}/.depdblock
+%{__rm} -rf %{buildroot}%{peardir}/.{filemap,lock,registry,channels,depdb,depdblock}
 
 # Install XML package description
 mkdir -p %{buildroot}%{xmldir}
```

Along with the changes, changelog entries are created, and commits or submit requests.
