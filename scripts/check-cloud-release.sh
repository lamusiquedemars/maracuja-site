#!/usr/bin/env bash
# LWS cPanel: download/verify Maracuja Site build from private GitHub Actions.
# No deployment, migration, server release change, or .env access.
set -Eeuo pipefail
umask 077
if [[ $# -lt 2 || $# -gt 3 || ! "$1" =~ ^[1-9][0-9]*$ || ! "$2" =~ ^[0-9a-f]{40}$ || ( $# -eq 3 && "$3" != '--stage' ) ]]; then
  echo 'Usage: bash check-cloud-release.sh BUILD_RUN_ID SHA_SITE_COMPLET [--stage]' >&2; exit 64
fi
run_id="$1"
expected_sha="$2"
mode="${3:-check}"
repo='lamusiquedemars/maracuja-control'
root="$HOME/apps/maracuja-site"
[[ -d "$root" && -L "$root/current" ]] || { echo 'Installation Maracuja Site absente.' >&2; exit 1; }
for bin in curl php unzip tar awk comm sed; do
  command -v "$bin" >/dev/null || { echo "Commande manquante : $bin" >&2; exit 1; }
done

work="$(mktemp -d)"
trap 'rm -rf -- "$work"' EXIT
printf 'Jeton GitHub (Actions: Read sur maracuja-control), puis Entrée : ' >&2
IFS= read -rs token </dev/tty
printf '\n' >&2
[[ "$token" =~ ^[A-Za-z0-9_]+$ ]] || { echo 'Jeton absent ou invalide.' >&2; exit 1; }
printf 'header = "Authorization: Bearer %s"\n' "$token" > "$work/curl.conf"
chmod 600 "$work/curl.conf"
unset token

api() {
  curl -fsSL --retry 2 --connect-timeout 12 --max-time 150 \
    --config "$work/curl.conf" -H 'Accept: application/vnd.github+json' "$@"
}
api "https://api.github.com/repos/$repo/actions/runs/$run_id" -o "$work/run.json"
php -r '
 $r=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);
 if (($r["event"]??null)!=="workflow_dispatch" || ($r["conclusion"]??null)!=="success"
 || ($r["head_branch"]??null)!=="main"
 || !str_ends_with((string)($r["path"]??""),".github/workflows/prepare-maracuja-site.yml")
 || ($r["repository"]["full_name"]??null)!=="lamusiquedemars/maracuja-control") exit(1);
' "$work/run.json" || { echo 'Run de préparation invalide.' >&2; exit 1; }

api "https://api.github.com/repos/$repo/actions/runs/$run_id/artifacts?per_page=100" -o "$work/artifacts.json"
artifact_id="$(php -r '
 $r=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);
 $wanted="maracuja-site-prepared-".$argv[2];
 $matches=array_values(array_filter($r["artifacts"]??[],static fn($a)=>
   ($a["name"]??null)===$wanted && ($a["expired"]??true)===false));
 if(count($matches)!==1 || !is_int($matches[0]["id"]??null)) exit(1);
 echo $matches[0]["id"];
' "$work/artifacts.json" "$run_id")" || { echo 'Archive GitHub absente/expirée.' >&2; exit 1; }

echo 'Téléchargement GitHub Actions (sans installation)…'
api "https://api.github.com/repos/$repo/actions/artifacts/$artifact_id/zip" -o "$work/build.zip"
short="${expected_sha:0:12}"
archive="maracuja-site_${short}.tar.gz"
manifest="maracuja-site_${short}.manifest.json"
entries="$(unzip -Z1 "$work/build.zip" | LC_ALL=C sort)"
wanted="$(printf '%s\n' 'build-evidence.json' "$archive" "$manifest" | LC_ALL=C sort)"
[[ "$entries" == "$wanted" ]] || { echo 'Fichiers de build inattendus.' >&2; exit 1; }
unzip -qq "$work/build.zip" -d "$work"
php -r '
 $m=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);
 $a=$argv[2]; $sha=$argv[3];
 if (($m["schema_version"]??null)!==1 || ($m["kind"]??null)!=="build-provenance"
 || ($m["status"]??null)!=="built-not-deployed"
 || ($m["product"]??null)!=="maracuja-site"
 || ($m["source"]["revision"]??null)!==$sha
 || ($m["artifact"]["filename"]??null)!==basename($a)
 || ($m["artifact"]["bytes"]??null)!==filesize($a)
 || !hash_equals((string)($m["artifact"]["sha256"]??""),hash_file("sha256",$a))) exit(1);
 echo "Empreinte SHA-256 : ".$m["artifact"]["sha256"]."\n";
 echo "Site Core : ".($m["site_core"]["revision"]??"non renseigné")."\n";
' "$work/$manifest" "$work/$archive" "$expected_sha" || {
  echo 'Empreinte ou provenance incohérente.' >&2; exit 1;
}
[[ "$(tar -xOzf "$work/$archive" ./REVISION | tr -d '\r\n')" == "$expected_sha" ]] || {
  echo 'SHA interne à l’archive différent.' >&2; exit 1;
}
tar -tzf "$work/$archive" | awk '
  /^\// || /(^|\/)\.\.(\/|$)/ || /(^|\/)\.env($|\/)/ {bad=1}
  END {exit bad}
' || { echo 'Chemin d’archive dangereux.' >&2; exit 1; }

active="$root/current/database/migrations"
[[ -d "$active" ]] || { echo 'Migrations actives introuvables.' >&2; exit 1; }
find "$active" -maxdepth 1 -type f -name '*.php' -printf '%f\n' | LC_ALL=C sort > "$work/old.txt"
tar -tzf "$work/$archive" |
  sed -n 's@^\./database/migrations/\([^/]*\.php\)$@\1@p' | LC_ALL=C sort > "$work/new.txt"
echo '--- Migrations ajoutées au build (noms) ---'
comm -13 "$work/old.txt" "$work/new.txt"
echo '--- Migrations absentes du build (noms) ---'
comm -23 "$work/old.txt" "$work/new.txt"
printf 'Nombre de migrations : installation %s, build %s\n' "$(wc -l < "$work/old.txt")" "$(wc -l < "$work/new.txt")"
echo '--- Migrations dont le contenu diffère ---'
mismatches=0
while IFS= read -r migration; do
  if ! cmp -s "$active/$migration" <(tar -xOzf "$work/$archive" "./database/migrations/$migration"); then
    echo "$migration"
    mismatches=$((mismatches + 1))
  fi
done < "$work/new.txt"
echo "Migrations différentes (contenu) : $mismatches"

if [[ "$(readlink -f "$root/current/storage")" != "$(readlink -f "$root/shared/storage")" ]]; then
  echo 'NOTE : ancienne release avec storage local. La nouvelle release utilisera shared/storage.'
fi

if [[ "$mode" != '--stage' ]]; then
  echo 'OK : archive GitHub téléchargée et vérifiée. Production inchangée.'
  exit 0
fi

# Stage strictly off the served release; fail rather than migrate automatically.
[[ "$mismatches" -eq 0 && "$(comm -13 "$work/old.txt" "$work/new.txt" | wc -l)" -eq 0
   && "$(comm -23 "$work/old.txt" "$work/new.txt" | wc -l)" -eq 0 ]] || {
  echo 'Migrations différentes. Préparation refusée en attente d’un plan spécifique.' >&2
  exit 1
}
[[ -d "$root/failed" && -d "$root/releases" && -d "$root/shared/storage/app" ]] || {
  echo 'Structure de releases ou stockage partagé indisponible.' >&2; exit 1;
}
[[ -f "$root/shared/.env" && "$(stat -c %a "$root/shared/.env")" = 600 ]] || {
  echo '.env partagé absent ou permissions non conformes.' >&2; exit 1;
}
# This deploy candidate has no changes to the currently served files.
name="$(date -u +%Y%m%d_%H%M%SZ)_${expected_sha:0:12}"
stage="$root/failed/$name"
[[ ! -e "$stage" && ! -L "$stage" && ! -e "$root/releases/$name" ]] || {
  echo 'Nom de préparation déjà utilisé.' >&2; exit 1;
}
mkdir -m 700 -- "$stage"
staged=0
cleanup_stage_on_failure() {
  if [[ "$staged" != 1 && -d "$stage" && ! -L "$stage" ]]; then
    rm -rf -- "$stage"
  fi
}
trap cleanup_stage_on_failure ERR
tar --no-same-owner --no-same-permissions -xzf "$work/$archive" -C "$stage"
[[ "$(tr -d '\r\n' < "$stage/REVISION")" = "$expected_sha"
   && -f "$stage/CORE_REVISION" && -f "$stage/public/index.php"
   && -f "$stage/vendor/autoload.php" ]] || {
  echo 'Release extraite invalide.' >&2; exit 1;
}
[[ ! -e "$stage/.env" && ! -L "$stage/.env" ]] || {
  echo '.env embarqué dans le build : refus.' >&2; exit 1;
}
if [[ -e "$stage/storage" || -L "$stage/storage" ]]; then
  [[ -d "$stage/storage" && ! -L "$stage/storage" ]] || {
    echo 'Stockage intégré inattendu.' >&2; exit 1;
  }
  mv "$stage/storage" "$stage/storage.template"
fi
ln -s ../../shared/.env "$stage/.env"
ln -s ../../shared/storage "$stage/storage"
[[ "$(readlink -f "$stage/storage")" = "$(readlink -f "$root/shared/storage")" ]] || {
  echo 'Lien vers les médias partagés invalide.' >&2; exit 1;
}
php -r '
 $m=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);
 file_put_contents($argv[2],$m["artifact"]["sha256"]."\n",LOCK_EX);
' "$work/$manifest" "$stage/ARTIFACT_SHA256"
chmod 600 "$stage/ARTIFACT_SHA256"
cp -- "$work/$manifest" "$stage/BUILD_MANIFEST.json"
chmod 600 "$stage/BUILD_MANIFEST.json"
staged=1
trap - ERR
echo "PRÉPARATION OK : $stage"
echo 'Aucune bascule de current, aucune migration, aucun changement sur la release active.'
echo 'Ne pas promouvoir cette release avant la vérification des médias et des fonctions métier.'
