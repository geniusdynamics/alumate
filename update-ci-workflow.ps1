# Update CI workflow to add feature test coverage reporting
$ciFile = ".github/workflows/ci.yml"
$content = Get-Content $ciFile -Raw

# Replace the feature test section
$oldFeatureTest = @'
      - name: Run Feature Tests Only
        run: |
          php artisan config:clear
          ./vendor/bin/pest --parallel --testsuite=Feature --configuration=phpunit.pgsql.xml
'@

$newFeatureTest = @'
      - name: Run Feature Tests Only
        run: |
          php artisan config:clear
          ./vendor/bin/pest --parallel --testsuite=Feature --configuration=phpunit.pgsql.xml --coverage --coverage-clover=tests/reports/coverage.feature.xml --coverage-html=tests/reports/coverage-html.feature --coverage-text=tests/reports/coverage.feature.txt

      - name: Upload Feature Test Reports
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: test-reports-feature
          path: |
            tests/reports/coverage.feature.xml
            tests/reports/coverage.feature.txt
          retention-days: 30

      - name: Upload Feature Coverage HTML
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: coverage-html-feature
          path: tests/reports/coverage-html.feature
          retention-days: 30
'@

$content = $content.Replace($oldFeatureTest, $newFeatureTest)
Set-Content $ciFile -Value $content -NoNewline

Write-Host "CI workflow updated successfully"