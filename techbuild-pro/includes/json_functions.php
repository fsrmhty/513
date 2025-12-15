<?php
// includes/json_functions.php

// JSON文件路径
define('PRODUCTS_JSON_PATH', dirname(__DIR__) . '/data/products.json');

/**
 * 确保数据目录和JSON文件存在
 */
function ensure_products_json() {
    $data_dir = dirname(PRODUCTS_JSON_PATH);
    
    // 创建数据目录
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    // 创建JSON文件如果不存在
    if (!file_exists(PRODUCTS_JSON_PATH)) {
        $initial_data = [
            'products' => [],
            'last_id' => 0
        ];
        file_put_contents(PRODUCTS_JSON_PATH, json_encode($initial_data, JSON_PRETTY_PRINT));
    }
}

/**
 * 获取所有产品
 */
function get_all_products() {
    ensure_products_json();
    
    $json_data = file_get_contents(PRODUCTS_JSON_PATH);
    $data = json_decode($json_data, true);
    
    return $data['products'] ?? [];
}

/**
 * 获取下一个产品ID
 */
function get_next_product_id() {
    ensure_products_json();
    
    $json_data = file_get_contents(PRODUCTS_JSON_PATH);
    $data = json_decode($json_data, true);
    
    $next_id = ($data['last_id'] ?? 0) + 1;
    
    // 更新last_id
    $data['last_id'] = $next_id;
    file_put_contents(PRODUCTS_JSON_PATH, json_encode($data, JSON_PRETTY_PRINT));
    
    return $next_id;
}

/**
 * 根据ID获取产品
 */
function get_product_by_id($id) {
    $products = get_all_products();
    
    foreach ($products as $product) {
        if ($product['id'] == $id) {
            return $product;
        }
    }
    
    return null;
}

/**
 * 添加新产品
 */
function add_product($product_data) {
    ensure_products_json();
    
    $json_data = file_get_contents(PRODUCTS_JSON_PATH);
    $data = json_decode($json_data, true);
    
    // 生成新ID
    $new_id = get_next_product_id();
    
    // 准备产品数据
    $new_product = [
        'id' => $new_id,
        'name' => $product_data['name'],
        'description' => $product_data['description'],
        'price' => (float)$product_data['price'],
        'type' => $product_data['type'],
        'category' => $product_data['category'] ?? '',
        'image' => $product_data['image'] ?? '',
        'stock' => $product_data['stock'] ?? 0,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // 添加到产品数组
    $data['products'][] = $new_product;
    
    // 保存到JSON文件
    if (file_put_contents(PRODUCTS_JSON_PATH, json_encode($data, JSON_PRETTY_PRINT))) {
        return $new_id;
    }
    
    return false;
}

/**
 * 更新产品
 */
function update_product($product_id, $product_data) {
    ensure_products_json();
    
    $json_data = file_get_contents(PRODUCTS_JSON_PATH);
    $data = json_decode($json_data, true);
    
    $updated = false;
    
    // 查找并更新产品
    foreach ($data['products'] as &$product) {
        if ($product['id'] == $product_id) {
            $product['name'] = $product_data['name'];
            $product['description'] = $product_data['description'];
            $product['price'] = (float)$product_data['price'];
            $product['type'] = $product_data['type'];
            $product['category'] = $product_data['category'] ?? '';
            $product['image'] = $product_data['image'] ?? $product['image'];
            $product['stock'] = $product_data['stock'] ?? $product['stock'];
            $product['updated_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        // 保存到JSON文件
        return file_put_contents(PRODUCTS_JSON_PATH, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    return false;
}

/**
 * 删除产品
 */
function delete_product($product_id) {
    ensure_products_json();
    
    $json_data = file_get_contents(PRODUCTS_JSON_PATH);
    $data = json_decode($json_data, true);
    
    // 过滤掉要删除的产品
    $data['products'] = array_filter($data['products'], function($product) use ($product_id) {
        return $product['id'] != $product_id;
    });
    
    // 重新索引数组
    $data['products'] = array_values($data['products']);
    
    // 保存到JSON文件
    return file_put_contents(PRODUCTS_JSON_PATH, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * 备份JSON文件
 */
function backup_products_json() {
    $backup_dir = dirname(PRODUCTS_JSON_PATH) . '/backup';
    
    // 创建备份目录
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $backup_file = $backup_dir . '/products_backup_' . date('Y-m-d_H-i-s') . '.json';
    
    return copy(PRODUCTS_JSON_PATH, $backup_file);
}
?>