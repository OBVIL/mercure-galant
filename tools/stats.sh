echo "xml:id	chars	head	bibl"
for SRCFILE in ../xml/*.xml
do
  FILENAME="${SRCFILE##*/}"
  FILENAME="${FILENAME%.*}"
  >&2 echo $SRCFILE
  xsltproc --stringparam filename "$FILENAME" stats.xsl $SRCFILE
done
