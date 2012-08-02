Kache
=====

Simple key-value based cache system, with file storage and reusing Wordpress options table.

Built as a proof of concept of how simple would be to do from scratch a cache component for Wordpress, not touching
the core.

As such, I don't initially plan to improve anything of it, nor the TODOs section, but anyway I'll leave them.

TODOs
-----
* File-based cache sucks, but the PoC was done on a IIS 7.0 running PHP Fast-CGI, so no Apache, no APC, no Memcached.
* Having to do one MySQL query also is far from optimal, specially being wp_options and not an specific table with
 proper keys. That would be an improvement.
* Another option I didn't checked (for being a system call) was the modified date of the file. Should benchmark to see 
if worth or slower than mysql.


Notes
-----
* Doesn't needs modifications of the WP Core, but it does need code changes in your theme(s), wherever you want to
cache something (included is a sidebar example).
* Includes a very trivial Logger used for debugging.
* $_SERVER['DOCUMENT_ROOT'] was needed on all file system operations due to IIS. Probably could be removed for other
web servers.