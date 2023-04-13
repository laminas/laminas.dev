#!/bin/bash
# Imported from https://github.com/platformsh/snippets/blob/73f24439bf1d02600bb6c56bdb46870f896a6bf5/src/install_swoole.sh
# License: MIT License

# contributors:
#  - Thomas DI LUCCIO <thomas.diluccio@platform.sh>
#  - Florent HUCK <florent.huck@platform.sh>
#  - Benjamin Hirsch <mail@benjaminhirsch.net>

run() {
    # Run the compilation process.
    cd $PLATFORM_CACHE_DIR || exit 1;

    SWOOLE_PROJECT=$1;
    SWOOLE_VERSION=$2;

    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    SWOOLE_BINARY="${SWOOLE_PROJECT}_v$2-php${PHP_VERSION}"
    SWOOLE_BINARY="${SWOOLE_BINARY//\./_}"

    if [ ! -f "${PLATFORM_CACHE_DIR}/${SWOOLE_BINARY}.so" ]; then
        ensure_source "$SWOOLE_PROJECT" "$SWOOLE_VERSION"
        compile_source "$SWOOLE_PROJECT"
        move_extension "$SWOOLE_PROJECT" "$SWOOLE_BINARY"
    fi

    copy_lib "$SWOOLE_PROJECT" "$SWOOLE_BINARY"
    enable_lib "$SWOOLE_PROJECT"
}

copy_lib() {
    echo "------------------------------------------------"
    echo " Copying compiled extension to PLATFORM_APP_DIR "
    echo "------------------------------------------------"

    SWOOLE_PROJECT=$1;
    SWOOLE_BINARY=$2;

    cp "${PLATFORM_CACHE_DIR}/${SWOOLE_BINARY}.so" "${PLATFORM_APP_DIR}/${SWOOLE_PROJECT}.so"
}

enable_lib() {
    echo "-------------------------------"
    echo " Enabling extension in php.ini "
    echo "-------------------------------"

    SWOOLE_PROJECT=$1;

    echo "extension=${PLATFORM_APP_DIR}/${SWOOLE_PROJECT}.so" >> $PLATFORM_APP_DIR/php.ini
}

move_extension() {
    echo "---------------------------------------"
    echo " Moving and caching compiled extension "
    echo "---------------------------------------"

    SWOOLE_PROJECT=$1;
    SWOOLE_BINARY=$2;

    mv "${PLATFORM_CACHE_DIR}/${SWOOLE_PROJECT}/swoole-src/modules/${SWOOLE_PROJECT}.so" "${PLATFORM_CACHE_DIR}/${SWOOLE_BINARY}.so"
}

ensure_source() {
    echo "---------------------------------------------------------------------"
    echo " Ensuring that the extension source code is available and up to date "
    echo "---------------------------------------------------------------------"

    SWOOLE_PROJECT=$1;
    SWOOLE_VERSION=$2;

    mkdir -p "$PLATFORM_CACHE_DIR/$SWOOLE_PROJECT"
    cd "$PLATFORM_CACHE_DIR/$SWOOLE_PROJECT" || exit 1;

    if [ -d "swoole-src" ]; then
        cd swoole-src || exit 1;
        git fetch --all --prune
    else
        git clone https://github.com/$SWOOLE_PROJECT/swoole-src.git swoole-src
        cd swoole-src || exit 1;
        git checkout "v$SWOOLE_VERSION"
    fi

    if [ -d "valgrind" ]; then
        cd valgrind || exit 1;
        git fetch --all --prune
    else
        git clone git://sourceware.org/git/valgrind.git valgrind
        cd valgrind || exit 1;
    fi
}

compile_source() {

    SWOOLE_PROJECT=$1;

    echo "--------------------"
    echo " Compiling valgrind "
    echo "--------------------"

    ./autogen.sh
    ./configure --prefix="$PLATFORM_CACHE_DIR/$SWOOLE_PROJECT/swoole-src"
    make
    make install

    echo "---------------------"
    echo " Compiling extension "
    echo "---------------------"

    cd ..
    phpize
    ./configure --enable-openssl \
                --enable-mysqlnd \
                --enable-sockets \
                --enable-http2 \
                --with-postgres
    make
}

ensure_environment() {
    # If not running in a Platform.sh build environment, do nothing.
    if [ -z "${PLATFORM_CACHE_DIR}" ]; then
        echo "Not running in a Platform.sh build environment.  Aborting Open Swoole installation."
        exit 0;
    fi
}

ensure_arguments() {
    # If no Swoole repository was specified, don't try to guess.
    if [ -z $1 ]; then
        echo "No version of the Swoole project specified. (swoole/openswoole)."
        exit 1;
    fi

    if [[ ! "$1" =~ ^(swoole|openswoole)$ ]]; then
        echo "The requested Swoole project is not supported: ${1} Aborting.\n"
        exit 1;
    fi

    # If no version was specified, don't try to guess.
    if [ -z $2 ]; then
        echo "No version of the ${1} extension specified.  You must specify a tagged version on the command line."
        exit 1;
    fi
}

ensure_environment
ensure_arguments "$1" "$2"

SWOOLE_PROJECT=$1;
SWOOLE_VERSION=$(sed "s/^[=v]*//i" <<< "$2" | tr '[:upper:]' '[:lower:]')

run "$SWOOLE_PROJECT" "$SWOOLE_VERSION"
