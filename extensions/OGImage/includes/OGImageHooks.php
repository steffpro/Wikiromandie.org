<?php

class OGImageHooks {

    public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {

        $title = $out->getTitle();
        if ( !$title || $title->isSpecialPage() ) {
            return true;
        }

        $default = "https://wikiromandie.org/images/logo.png";
        $img = null;

        $services = MediaWiki\MediaWikiServices::getInstance();

        /* ----------------------------
         * 1️⃣ IMAGE INFOBOX (SMW)
         * ---------------------------- */
        if ( class_exists('\SMW\StoreFactory') ) {
            $store = \SMW\StoreFactory::getStore();
            $subject = \SMW\DIWikiPage::newFromTitle( $title );
            $property = \SMW\DIProperty::newFromUserLabel( 'Image' );
            $values = $store->getPropertyValues( $subject, $property );

            if ( !empty( $values ) ) {
                $v = reset($values);
                $fileTitle = Title::newFromText( $v->getString(), NS_FILE );
                $img = self::makeOGImage( $fileTitle );
            }
        }

        /* ------------------------------------------
         * 2️⃣ SI PAS SMW : PREMIÈRE IMAGE DE LA PAGE
         * ------------------------------------------ */
        if ( !$img ) {

            $wikiPage = $services->getWikiPageFactory()->newFromTitle( $title );
            $content = $wikiPage->getContent();

            if ( $content ) {
                $text = ContentHandler::getContentText( $content );

                if ( preg_match('/\\[\\[(?:File|Fichier|Image):([^\\]|]+)(?:\\|[^\\]]*)?\\]\\]/i', $text, $m ) ) {
                    $fileTitle = Title::makeTitleSafe( NS_FILE, trim($m[1]) );
                    $img = self::makeOGImage( $fileTitle );
                }
            }
        }

        /* ----------------------------
         * 3️⃣ FALLBACK
         * ---------------------------- */
        if ( !$img ) {
            $img = $default;
        }

        /* ----------------------------
         * INJECT META
         * ---------------------------- */
        $out->addMeta( 'og:image', $img );
        $out->addMeta( 'twitter:image', $img );

        return true;
    }

    /**
     * Génère une image OG 1200x630 dans /images/ogcache/
     */
    public static function makeOGImage( $fileTitle ) {

        if ( !$fileTitle ) return null;

        $services = MediaWiki\MediaWikiServices::getInstance();
        $file = $services->getRepoGroup()->findFile( $fileTitle );

        if ( !$file ) return null;

        $src = $file->getLocalRefPath();
        if ( !$src || !file_exists($src) ) {
            return $file->getFullUrl(); // fallback brut
        }

        $hash = md5( $src . filemtime($src) );
        $destName = "og-$hash.jpg";

        $dir = wfTempDir() . "/ogcache";
        if ( !is_dir($dir) ) mkdir($dir, 0777, true);

        $dest = "$dir/$destName";

        if ( !file_exists($dest) ) {

            $img = new Imagick($src);
            $img->setImageFormat("jpeg");
            $img->cropThumbnailImage(1200, 630);
            $img->writeImage($dest);
            $img->destroy();
        }

        // copie vers /images/ogcache/
        $publicDir = $_SERVER['DOCUMENT_ROOT'] . "/images/ogcache/";
        if ( !is_dir($publicDir) ) mkdir($publicDir, 0777, true);

        copy($dest, $publicDir . $destName);

        return "/images/ogcache/$destName";
    }
}
