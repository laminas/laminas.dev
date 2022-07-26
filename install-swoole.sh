#!/bin/bash

set -e

run() {
    # Run the compilation process.
    cd "$PLATFORM_CACHE_DIR" || exit 1;

    if [[ ! -f "${PLATFORM_CACHE_DIR}/swoole/COMPILED_VERSION" ]]; then
        ensure_source
        checkout_version "$1"
        compile_source
    elif [[ "$(cat "${PLATFORM_CACHE_DIR}/swoole/COMPILED_VERSION")" != "${SWOOLE_VERSION}" ]]; then
        ensure_source
        checkout_version "$1"
        compile_source
    fi

    copy_lib
    enable_lib
}

enable_lib() {
    # Tell PHP to enable the extension.
    echo "Enabling Swoole extension."
    echo "extension=${PLATFORM_APP_DIR}/swoole.so" >> "${PLATFORM_APP_DIR}/php.ini"
}

copy_lib() {
    # Copy the compiled library to the application directory.
    echo "Installing Swoole extension."
    cp "${PLATFORM_CACHE_DIR}/swoole/modules/swoole.so" "${PLATFORM_APP_DIR}"
}

checkout_version() {
    # Check out the specific Git tag that we want to build.
    git checkout "$1"
}

gitignore_version_file() {
    if [ ! -f "${PLATFORM_CACHE_DIR}/swoole/COMPILED_VERSION" ]; then
        echo "COMPILED_VERSION" >> "${PLATFORM_CACHE_DIR}/swoole/.git/info/exclude"
    fi
}

ensure_source() {
    # Ensure that the extension source code is available and up to date.
    if [ -d "swoole" ]; then
        cd swoole || exit 1;
        git fetch --all --prune
    else
        git clone https://github.com/swoole/swoole-src.git swoole
        cd swoole || exit 1;
    fi
}

compile_source() {
    # Compile the extension.
    phpize clean
    ./configure --enable-swoole --enable-sockets --enable-swoole-json
    make
    echo -n "${SWOOLE_VERSION}" > COMPILED_VERSION
}

ensure_environment() {
    # If not running in a Platform.sh build environment, do nothing.
    if [[ -z "${PLATFORM_CACHE_DIR}" ]]; then
        echo "Not running in a Platform.sh build environment.  Aborting Swoole installation."
        exit 0;
    fi
}

ensure_arguments() {
    # If no version was specified, don't try to guess.
    if [ -z "$1" ]; then
        echo "No version of the Swoole extension specified.  You must specify a tagged version on the command line."
        exit 1;
    fi
}

ensure_environment
ensure_arguments "$1"
run "$1"
