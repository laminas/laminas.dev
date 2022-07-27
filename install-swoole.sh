#!/bin/bash

set -e

run() {
    # Run the compilation process.
    cd "${PLATFORM_CACHE_DIR}" || exit 1;

    local swoole_version="$1"
    local swoole_type="$2"

    local php_version
    php_version="$(php -r "echo PHP_VERSION;")"

    local version_prefix="v${swoole_version}-php${php_version}"
    version_prefix="${version_prefix//\./_}"

    if [ ! -f "${PLATFORM_CACHE_DIR}/${swoole_type}/swoole-src/modules/${version_prefix}-${swoole_type}.so" ]; then
        ensure_source "$swoole_type"
        checkout_version "${PLATFORM_CACHE_DIR}/${swoole_type}/swoole-src" "v${swoole_version}"
        compile_source "${PLATFORM_CACHE_DIR}/${swoole_type}"
        move_extension "$swoole_type" "$version_prefix"
    fi

    copy_lib "$2" "$version_prefix"
    enable_lib "$2"
}

enable_lib() {
    # Tell PHP to enable the extension.
    local swoole_type="$1"

    echo "---------------------------------"
    echo "Enabling ${swoole_type} extension."
    echo -e "\nextension=${PLATFORM_APP_DIR}/${swoole_type}.so" >> "${PLATFORM_APP_DIR}/php.ini"
}

copy_lib() {
    # Copy the compiled library to the application directory.
    local swoole_type="$1"
    local version_prefix="$2"

    echo "---------------------------------"
    echo "Installing ${swoole_type} extension."
    cp "${PLATFORM_CACHE_DIR}/${swoole_type}/swoole-src/modules/${version_prefix}-${swoole_type}.so" "${PLATFORM_APP_DIR}/${swoole_type}.so"
}

checkout_version () {
    local checkout_dir="$1"
    local version="$2"
    # Check out the specific Git tag that we want to build.
    cd "$checkout_dir"
    git checkout "$version"
}

ensure_source() {
    # Ensure that the extension source code is available and up to date.
    local swoole_type="$1"

    mkdir -p "${PLATFORM_CACHE_DIR}/${swoole_type}"
    cd "${PLATFORM_CACHE_DIR}/${swoole_type}"

    if [ -d "swoole-src" ]; then
        cd swoole-src || exit 1;
        git fetch --all --prune
    else
        git clone "https://github.com/${swoole_type}/swoole-src.git"
        cd swoole-src || exit 1;
    fi

    cd "${PLATFORM_CACHE_DIR}/${swoole_type}";

    if [ -d "valgrind" ]; then
        cd valgrind || exit 1
        git fetch --all --prune
    else
        git clone git://sourceware.org/git/valgrind.git valgrind
        cd valgrind || exit 1
    fi
}

compile_source() {
    # Compile the extension.
    local base_dir="$1"

    echo "Compiling valgrind"
    cd "${base_dir}/valgrind"
    ./autogen.sh
    ./configure --prefix="${base_dir}/swoole-src"
    make
    make install

    echo "Compiling (Open)Swoole"
    cd "${base_dir}/swoole-src"
    phpize clean
    phpize
    ./configure
    make
}

move_extension() {
    local swoole_type="$1"
    local version_prefix="$2"

    echo "---------------------------------"
    echo "Moving built extension to identified folder."
    mv "${PLATFORM_CACHE_DIR}/${swoole_type}/swoole-src/modules/${swoole_type}.so" "${PLATFORM_CACHE_DIR}/${swoole_type}/swoole-src/modules/${version_prefix}-${swoole_type}.so"
}

ensure_environment() {
    # If not running in a Platform.sh build environment, do nothing.
    if [[ -z "${PLATFORM_CACHE_DIR}" ]]; then
        echo "Not running in a Platform.sh build environment.  Aborting Swoole installation."
        exit 0;
    fi
}

ensure_arguments() {
    # If no Swoole repository was specified, don't try to guess.
    if [ -z "$1" ]; then
        echo "No version of the Swoole project specified. (swoole/openswoole)."
        exit 1;
    fi

    if [[ ! "$1" =~ ^(swoole|openswoole)$ ]]; then
        echo "The requested Swoole project is not supported: ${1}"
        echo "Aborting."
        exit 1;
    fi

    # If no version was specified, don't try to guess.
    if [ -z "$2" ]; then
        echo "No version of the ${1} extension specified.  You must specify a tagged version on the command line."
        exit 1;
    fi
}

ensure_environment
ensure_arguments "$1" "$2"

SWOOLE_PROJECT=$1;
SWOOLE_VERSION=$(sed "s/^[=v]*//i" <<< "$2" | tr '[:upper:]' '[:lower:]')
run "$SWOOLE_VERSION" "$SWOOLE_PROJECT"
