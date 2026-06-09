<?php

namespace App\Support;

class PumpSelectorUiConfig
{
    /**
     * @return list<array{id: string, icon: string, label: string, featured: bool}>
     */
    public static function applications(): array
    {
        return [
            ['id' => 'hydrofor_apartment', 'icon' => 'grid', 'label' => __('shop.pump_app_hydrofor_apt'), 'featured' => true],
            ['id' => 'hydrofor_villa', 'icon' => 'user', 'label' => __('shop.pump_app_hydrofor_villa'), 'featured' => true],
            ['id' => 'submersible_well', 'icon' => 'arrow-path', 'label' => __('shop.pump_app_well'), 'featured' => true],
            ['id' => 'jet_shallow', 'icon' => 'truck', 'label' => __('shop.pump_app_jet'), 'featured' => false],
            ['id' => 'drainage', 'icon' => 'shield', 'label' => __('shop.pump_app_drainage'), 'featured' => true],
            ['id' => 'septic', 'icon' => 'shield', 'label' => __('shop.pump_app_septic'), 'featured' => false],
            ['id' => 'irrigation', 'icon' => 'star', 'label' => __('shop.pump_app_irrigation'), 'featured' => true],
            ['id' => 'circulation', 'icon' => 'arrow-path', 'label' => __('shop.pump_app_circulation'), 'featured' => false],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function clientConfig(): array
    {
        return [
            'loading' => __('shop.pump_selector_loading'),
            'error' => __('shop.pump_selector_error'),
            'inStock' => __('shop.in_stock'),
            'outOfStock' => __('shop.out_of_stock'),
            'viewProduct' => __('shop.view_product'),
            'matchLabel' => __('shop.pump_selector_match'),
            'stepLabels' => [
                __('shop.pump_selector_step_label_1'),
                __('shop.pump_selector_step_label_2'),
                __('shop.pump_selector_step_label_3'),
            ],
            'fields' => [
                'apartments' => ['label' => __('shop.pump_field_apartments'), 'type' => 'number', 'min' => 1, 'max' => 500, 'default' => 20],
                'floors' => ['label' => __('shop.pump_field_floors'), 'type' => 'number', 'min' => 1, 'max' => 40, 'default' => 5],
                'bathrooms' => ['label' => __('shop.pump_field_bathrooms'), 'type' => 'number', 'min' => 1, 'max' => 20, 'default' => 3],
                'depth' => ['label' => __('shop.pump_field_depth'), 'type' => 'number', 'min' => 1, 'max' => 200, 'default' => 40],
                'suction_depth' => ['label' => __('shop.pump_field_suction'), 'type' => 'number', 'min' => 1, 'max' => 8, 'default' => 4],
                'volume_m3' => ['label' => __('shop.pump_field_volume'), 'type' => 'number', 'min' => 1, 'max' => 500, 'default' => 12],
                'drain_hours' => ['label' => __('shop.pump_field_drain_hours'), 'type' => 'number', 'min' => 1, 'max' => 24, 'default' => 2],
                'distance' => ['label' => __('shop.pump_field_distance'), 'type' => 'number', 'min' => 5, 'max' => 100, 'default' => 25],
                'area_m2' => ['label' => __('shop.pump_field_area'), 'type' => 'number', 'min' => 50, 'max' => 50000, 'default' => 800],
                'heated_area_m2' => ['label' => __('shop.pump_field_heated_area'), 'type' => 'number', 'min' => 40, 'max' => 2000, 'default' => 140],
                'space_m2' => ['label' => __('shop.pump_field_space'), 'type' => 'number', 'min' => 10, 'max' => 5000, 'default' => 120],
                'height_m' => ['label' => __('shop.pump_field_height'), 'type' => 'number', 'min' => 2, 'max' => 15, 'default' => 4],
                'usage' => [
                    'label' => __('shop.pump_field_usage'),
                    'type' => 'select',
                    'options' => [
                        ['value' => 'household', 'label' => __('shop.pump_usage_household')],
                        ['value' => 'garden', 'label' => __('shop.pump_usage_garden')],
                        ['value' => 'agriculture', 'label' => __('shop.pump_usage_agriculture')],
                        ['value' => 'livestock', 'label' => __('shop.pump_usage_livestock')],
                    ],
                ],
                'method' => [
                    'label' => __('shop.pump_field_method'),
                    'type' => 'select',
                    'options' => [
                        ['value' => 'sprinkler', 'label' => __('shop.pump_method_sprinkler')],
                        ['value' => 'drip', 'label' => __('shop.pump_method_drip')],
                    ],
                ],
                'lift' => [
                    'label' => __('shop.pump_field_lift'),
                    'type' => 'select',
                    'options' => [
                        ['value' => 'low', 'label' => __('shop.pump_lift_low')],
                        ['value' => 'medium', 'label' => __('shop.pump_lift_medium')],
                        ['value' => 'high', 'label' => __('shop.pump_lift_high')],
                    ],
                ],
                'environment' => [
                    'label' => __('shop.pump_field_environment'),
                    'type' => 'select',
                    'options' => [
                        ['value' => 'workshop', 'label' => __('shop.pump_env_workshop')],
                        ['value' => 'warehouse', 'label' => __('shop.pump_env_warehouse')],
                        ['value' => 'kitchen', 'label' => __('shop.pump_env_kitchen')],
                    ],
                ],
            ],
            'fieldSets' => [
                'hydrofor_apartment' => ['apartments', 'floors'],
                'hydrofor_villa' => ['bathrooms', 'floors'],
                'submersible_well' => ['depth', 'usage'],
                'jet_shallow' => ['suction_depth', 'usage'],
                'drainage' => ['volume_m3', 'drain_hours', 'lift'],
                'septic' => ['distance'],
                'irrigation' => ['area_m2', 'method'],
                'circulation' => ['heated_area_m2', 'floors'],
            ],
        ];
    }
}
