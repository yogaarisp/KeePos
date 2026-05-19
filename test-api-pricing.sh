#!/bin/bash

echo "🧪 Testing API Pricing Endpoints..."

echo ""
echo "📡 Testing Public Plans Endpoint:"
echo "URL: https://pos.keetech.my.id/api/subscriptions/plans/public"
echo ""

# Test the public plans endpoint
response=$(curl -s "https://pos.keetech.my.id/api/subscriptions/plans/public")

# Check if response contains expected prices
if echo "$response" | grep -q '"price":99000'; then
    echo "✅ BASIC price: Rp 99,000 - CORRECT"
else
    echo "❌ BASIC price: NOT FOUND or INCORRECT"
fi

if echo "$response" | grep -q '"price":299000'; then
    echo "✅ PRO price: Rp 299,000 - CORRECT"
else
    echo "❌ PRO price: NOT FOUND or INCORRECT"
    if echo "$response" | grep -q '"price":249000'; then
        echo "⚠️  Found old PRO price: Rp 249,000 - NEEDS UPDATE"
    fi
fi

echo ""
echo "📋 Full Response:"
echo "$response" | jq '.' 2>/dev/null || echo "$response"

echo ""
echo "🔍 Price Summary:"
echo "$response" | jq '.data.basic.price_formatted, .data.pro.price_formatted' 2>/dev/null || echo "Could not parse JSON"