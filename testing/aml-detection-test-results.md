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


**Scope note:** These tests cover the `LARGE_TXN` rule only. They do not establish that every AML rule or every application component has been tested.




