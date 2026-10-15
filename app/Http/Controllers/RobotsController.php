<?php
namespace App\Http\Controllers;
class RobotsController {public function __invoke(){return response("User-agent: *\nDisallow: /admin\nDisallow: /cart\nDisallow: /checkout\nDisallow: /order/\nSitemap: ".url('/sitemap.xml')."\n",200)->header('Content-Type','text/plain');}}
