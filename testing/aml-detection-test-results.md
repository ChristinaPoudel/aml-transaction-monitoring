# AML Transaction Monitoring - Detection Test Results



## 1. Objective



Verify that the PHP transaction-monitoring script detects transactions at or above the configured threshold, assigns the expected severity, and avoids inserting duplicate alerts when run again.



## 2. Test Environment



- Database: `aml_engine_test`

- Application: PHP with PostgreSQL

- Script under test: `php/detect_test.php`

- Detection rule: `LARGE_TXN`

- Alert threshold: $10,000.00

- High-severity threshold: $50,000.00



Testing was performed against an isolated test database to avoid modifying the original project data.



## 3. Test Cases and Results



| Test ID | Scenario | Test Input | Expected Result | Actual Result | Status |

|---|---|---:|---|---|---|

| AML-TC-01 | Below alert threshold | $9,999.99 | No alert | No alert created | PASS |

| AML-TC-02 | At alert threshold | $10,000.00 | MEDIUM alert | MEDIUM alert created | PASS |

| AML-TC-03 | At high-severity threshold | $50,000.00 | HIGH alert | HIGH alert created | PASS |

| AML-TC-04 | Repeat detection | Run detector a second time | No duplicate alerts | Script returned 0 new alerts | PASS |

| AML-TC-05 | Verify persisted alert records | Query PostgreSQL | Two alerts for two distinct transactions | 2 alerts; 2 distinct transactions | PASS |



## 4. Evidence and Observations



- The first detector execution returned `2`, indicating two new alerts were inserted.

- PostgreSQL confirmed that the $10,000 transaction received a `MEDIUM` alert with status `OPEN`.

- PostgreSQL confirmed that the $50,000 transaction received a `HIGH` alert with status `OPEN`.

- The $9,999.99 transaction did not generate a `LARGE_TXN` alert.

- The second detector execution returned `0`.

- The final database count remained at two alerts for two distinct transactions.



## 5. Conclusion



All five test cases passed for the scenarios tested. The results support the expected threshold behavior, severity classification, and repeat-run behavior of the detection script in the isolated test environment.


## 6. Additional Connection-Failure Experiment

**Objective:** Check how a PDO connection attempt behaves when the configured database does not exist.

| Test ID | Scenario | Expected Result | Actual Result | Status |
|---|---|---|---|---|
| AML-TC-06 | Connect using a deliberately invalid database name in a test-only connection function | Catch the connection exception without displaying database details | Test printed `PASS: db() connection failure was caught.` | PASS |

**Limitation:** This experiment used a test-only replica of the connection function. It did not directly test the application's production `db()` function in `php/config.php`, so further testing is needed to verify the application's actual error-handling behavior.

## 7. Additional Structuring Detection Tests

**Objective:** Evaluate a test-only detector that identifies transactions between $3,000 and $9,900 when a customer has at least five qualifying transactions within a rolling two-hour window.

**Test environment:** `aml_engine_test` (isolated PostgreSQL test database).

**Script under test:** `php/detect_structuring_test.php` (test-only helper; not the production detector).

### Test Cases and Results

| Test ID | Scenario | Expected Result | Actual Result | Status |
|---|---|---|---|---|
| AML-TC-07 | Four qualifying transactions within two hours | No structuring alert | No new alert created | PASS |
| AML-TC-08 | Five qualifying transactions within two hours | Structuring alert created | One alert created for the threshold-completing transaction | PASS |
| AML-TC-09 | Five qualifying transactions spread across time, with no qualifying five-transaction rolling window | No alert for the test customer | Zero alerts for the test customer | PASS |
| AML-TC-10 | Transaction below the $3,000 minimum | Transaction does not qualify for structuring detection | No structuring alert for that transaction | PASS |
| AML-TC-11 | Five qualifying transactions spanning exactly two hours | Structuring alert created if both endpoints are included | One HIGH, OPEN alert created for the final transaction | PASS |
| AML-TC-12 | Rerun after an alert already exists | No duplicate alert | Zero new alerts created | PASS |

### Evidence and Observations

- The detector selected transactions in the inclusive amount range of $3,000 to $9,900.
- Four qualifying transactions within two hours did not trigger an alert.
- Five qualifying transactions within a two-hour window triggered one alert for the transaction completing the threshold.
- The customer whose five qualifying transactions were spread across time received no structuring alert.
- The $2,999.99 transaction was excluded from the qualifying amount range.
- Five qualifying transactions spanning exactly two hours triggered an alert for the final transaction, consistent with the inclusive PostgreSQL `BETWEEN` condition.
- Re-running the detector created no duplicate alerts for previously flagged transactions.

### Limitations

These tests were run using a separate test-only helper and an isolated test database. They do not demonstrate that the production detector implements structuring detection.

The current helper inserts alerts for transactions that meet the rolling-window condition, with duplicate checks based on transaction ID and rule code. Further review is needed for multi-account customer scenarios, concurrent execution, exact threshold behavior, and whether the intended business rule should create one alert per qualifying transaction or one alert per customer/window.

### Conclusion

The tested scenarios produced the expected results for the test helper. These results provide evidence of its amount-range filtering, rolling two-hour count, boundary handling, and repeat-run duplicate prevention. Production implementation and broader validation remain outstanding.

**Scope note:** The original application detector tests cover the `LARGE_TXN` rule. The additional `STRUCTURING` tests use a separate test-only helper and do not establish that structuring detection is implemented in the production application. These tests do not cover every AML rule or every application component.




