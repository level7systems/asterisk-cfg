# Asterisk PBX - reference configuration

This repository contains complete set of configuration files for Asterisk PBX.

This configuration files has been tested with Asterisk 11 and Asterisk 13.


## Usage instructions

### Install Asterisk

#### Debian

Install required packages:
```
apt-get install build-essential openssl libxml2-dev libncurses5-dev uuid-dev sqlite3 libsqlite3-dev pkg-config libjansson-dev
```

Clean any previous Asterisk versions in `/usr/src` directory:
```
cd /usr/src/
rm -rf asterisk*
```

Get Asterisk source:

Asterisk 11
```
wget http://downloads.asterisk.org/pub/telephony/asterisk/asterisk-11-current.tar.gz
```

Asterisk 13
```
wget http://downloads.asterisk.org/pub/telephony/asterisk/asterisk-13-current.tar.gz
```


Compile and install:
```
tar xf asterisk-*-current.tar.gz
cd asterisk-*
./configure
make && make install
```


## Get configuration files

Backup configuration files which come with Asterisk default installation:

```
cd /etc/
mv asterisk asterisk.orig
```

Install `git` (if not already present on your system):
```
apt-get install git-core
```

Checkout configuration files:
```
git clone https://github.com/level7systems/asterisk-cfg.git asterisk
```

