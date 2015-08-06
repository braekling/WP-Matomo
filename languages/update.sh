#!/bin/bash
echo "== Fetch updated language files ===="
tx pull -a
echo "== Convert po to po ================"
for file in `find . -name "*.po"` ; do echo $file; msgfmt -v -o ${file/.po/.mo} $file ; done
