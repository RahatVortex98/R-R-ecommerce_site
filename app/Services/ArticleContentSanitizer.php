<?php
namespace App\Services;
class ArticleContentSanitizer {
    public function sanitize(string $html): string {
        $config=\HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl',null);
        $config->set('HTML.Allowed','p,br,strong,em,u,h2,h3,h4,ul,ol,li,blockquote,a[href|title]');
        $config->set('URI.AllowedSchemes',['https'=>true,'http'=>true,'mailto'=>true]);
        return (new \HTMLPurifier($config))->purify($html);
    }
}
