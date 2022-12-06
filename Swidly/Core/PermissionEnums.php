<?php

namespace Swidly;

class PermissionEnums
{
    const MANG_BILLING = 1;
    const DELETE_TEAM = 2;
    const ADD_TEAM = 4;
    const CREATE_BROD = 8;
    const ACCESS_LIB = 16;
    const UPLOAD_ASSETS = 32;
    const CREATE_BANNERS = 64;
    const CREATE_BRAND = 128;
    const ADD_REMOVE_DEST = 256;
    const CHANG_THEME = 512;
    const SHOW_COMMENTS = 1024;
    const MANG_SPEAKERS = 2048;
    const MANG_VIDEOS = 4096;
    const SHOW_ASSETS = 8192;

    const ROLE_OWNER = self::MANG_BILLING | self::DELETE_TEAM | self::ADD_TEAM | self::CREATE_BROD | self::ACCESS_LIB | self::UPLOAD_ASSETS | self::CREATE_BANNERS | self::ADD_REMOVE_DEST | self::CHANG_THEME | self::SHOW_COMMENTS | self::MANG_SPEAKERS | self::MANG_VIDEOS | self::SHOW_ASSETS | self::CREATE_BRAND;
    const ROLE_ADMIN = self::ADD_TEAM | self::CREATE_BROD | self::ACCESS_LIB | self::UPLOAD_ASSETS | self::CREATE_BANNERS | self::ADD_REMOVE_DEST | self::CHANG_THEME | self::SHOW_COMMENTS | self::MANG_SPEAKERS | self::MANG_VIDEOS | self::SHOW_ASSETS | self::CREATE_BRAND;
    const ROLE_CO_HOST = self::SHOW_COMMENTS | self::MANG_SPEAKERS | self::MANG_VIDEOS | self::SHOW_ASSETS | self::CREATE_BRAND;
}