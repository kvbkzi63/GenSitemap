<?php
/**
 * Created by PhpStorm.
 * User: Will
 * Date: 2022/2/11 
 */
class SeoService
{

    public function CreateSEOXml($domain)
    {
        if (file_exists('sitemap.xml')) {
            unlink('sitemap.xml');
        }
        $date= date('Y-m-d');
        $writer = new XMLWriter();
        $writer->openURI('sitemap.xml');
        $writer->startDocument('1.0', 'UTF-8');
        $hash_map = array();
        $this->recursiveParseLink("", $hash_map);
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $writer->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $writer->writeAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
        foreach($hash_map as $key=>$value)
        {
            if ($key !== $domain) {
                Yii::log('xml loc :' . $key);
                $writer->startElement('url');
                $writer->writeElement('loc', $domain.$key); 
                $writer->writeElement('lastmod', $date);
                $writer->writeElement('priority', '0.80');
                $writer->endElement();
            }
        }  
        $writer->endElement(); 
        $writer->endDocument();
        $writer->flush();
    }
    private function recursiveParseLink($nextLink = "", &$hash_map)
    {
        $domain = "https://www.google.com.tw" . $nextLink;
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
            'http' => ['ignore_errors' => true]
        );  
        $getUrl = parse_url($domain);
        $domain = $getUrl['scheme'] . "://" . $getUrl['host'];
        if(isset($getUrl['path'])) $domain .= $getUrl['path'];
        if(isset($getUrl['query'])) $domain .= "?" . urlencode($getUrl['query']);
        $data = file_get_contents("".$domain."", false, stream_context_create($arrContextOptions));
        if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$http_response_header[0], $out ) ){
            $reponse_code = intval($out[1]);
        }
        if($reponse_code == 200){
            $data = strip_tags($data, "<a>");
            $d = preg_split("/<\/a>/", $data);
            if (empty($hash_map)) {
                $hash_map[$domain] = array();
            }
            foreach ($d as $k => $u) {
                if (strpos($u, "<a href=") !== FALSE) {
                    $arr = explode('"', $u);
                    if (count($arr) > 2) {
                        $link =  str_replace("https://www.google.com.tw", "", $arr[1]);
                        if (!array_key_exists($link, $hash_map) && substr($link, 0, 1) === "/" && !strpos($link, "#")) {
                            $hash_map[$link] = array();
                            $this->recursiveParseLink($link, $hash_map);
                        }
                    }
                }
            }
            return true;
        }
    }
}
?>