#!/bin/bash
set -e

FILE="/app/extensions/SemanticMediaWiki/src/MediaWiki/Content/SchemaContentHandler.php"

sed -i -E \
's|parent::__construct\([[:space:]]*CONTENT_MODEL_SMW_SCHEMA[[:space:]]*,[[:space:]]*\[[^]]*\][[:space:]]*\)|parent::__construct( CONTENT_MODEL_SMW_SCHEMA, null )|' \
"$FILE"