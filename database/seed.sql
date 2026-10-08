--
-- PostgreSQL database dump
--



-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: customers; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.customers VALUES (1, 'John Doe', '2026-10-08 11:53:26.815839');
INSERT INTO public.customers VALUES (2, 'Jane Smith', '2026-10-08 11:53:26.815839');
INSERT INTO public.customers VALUES (3, 'Alice Johnson', '2026-10-08 11:53:26.815839');
INSERT INTO public.customers VALUES (52, 'Alice Smith', '2026-10-08 15:07:57.105332');
INSERT INTO public.customers VALUES (53, 'Michael Brown', '2026-10-08 15:07:57.105332');
INSERT INTO public.customers VALUES (54, 'Sarah Wilson', '2026-10-08 15:07:57.105332');
INSERT INTO public.customers VALUES (55, 'David Miller', '2026-10-08 15:07:57.105332');
INSERT INTO public.customers VALUES (56, 'Emma Davis', '2026-10-08 15:07:57.105332');
INSERT INTO public.customers VALUES (57, 'Daniel Anderson', '2026-10-08 15:07:57.105332');
INSERT INTO public.customers VALUES (58, 'Sophia Taylor', '2026-10-08 15:07:57.105332');
INSERT INTO public.customers VALUES (59, 'James Thomas', '2026-10-08 15:07:57.105332');
INSERT INTO public.customers VALUES (60, 'Olivia Moore', '2026-10-08 15:07:57.105332');
INSERT INTO public.customers VALUES (61, 'William Jackson', '2026-10-08 15:07:57.105332');
INSERT INTO public.customers VALUES (62, 'Mia White', '2026-10-08 15:07:57.105332');
INSERT INTO public.customers VALUES (63, 'Robert Harris', '2026-10-08 15:07:57.105332');
INSERT INTO public.customers VALUES (64, 'Robert Jonshon', '2026-10-08 16:57:45.299385');


--
-- Data for Name: accounts; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.accounts VALUES (1, 1, 'ACC1001', 'CHECKING');
INSERT INTO public.accounts VALUES (2, 2, 'ACC1002', 'SAVINGS');
INSERT INTO public.accounts VALUES (3, 3, 'ACC1003', 'BUSINESS');
INSERT INTO public.accounts VALUES (68, 52, 'ACC1052', 'CHECKING');
INSERT INTO public.accounts VALUES (69, 53, 'ACC1053', 'CHECKING');
INSERT INTO public.accounts VALUES (70, 54, 'ACC1054', 'CHECKING');
INSERT INTO public.accounts VALUES (71, 55, 'ACC1055', 'CHECKING');
INSERT INTO public.accounts VALUES (72, 56, 'ACC1056', 'CHECKING');
INSERT INTO public.accounts VALUES (73, 57, 'ACC1057', 'CHECKING');
INSERT INTO public.accounts VALUES (74, 58, 'ACC1058', 'CHECKING');
INSERT INTO public.accounts VALUES (75, 59, 'ACC1059', 'CHECKING');
INSERT INTO public.accounts VALUES (76, 60, 'ACC1060', 'CHECKING');
INSERT INTO public.accounts VALUES (77, 61, 'ACC1061', 'CHECKING');
INSERT INTO public.accounts VALUES (78, 62, 'ACC1062', 'CHECKING');
INSERT INTO public.accounts VALUES (79, 63, 'ACC1063', 'CHECKING');


--
-- Data for Name: geo_locations; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.geo_locations VALUES (1, 'USA', 'LOW');
INSERT INTO public.geo_locations VALUES (2, 'PRK', 'HIGH');
INSERT INTO public.geo_locations VALUES (3, 'CAN', 'LOW');


--
-- Data for Name: merchant_types; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.merchant_types VALUES (1, '7995', 'Gambling', 9);
INSERT INTO public.merchant_types VALUES (2, '5944', 'Jewelry', 8);
INSERT INTO public.merchant_types VALUES (3, '5411', 'Grocery', 2);


--
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.transactions VALUES (1, 1, 1, 1, 12000.00, 'TRANSFER_OUT', '2026-10-08 11:53:26.815839');
INSERT INTO public.transactions VALUES (2, 2, 2, 2, 8500.00, 'CARD_PURCHASE', '2026-10-08 10:53:26.815839');
INSERT INTO public.transactions VALUES (3, 3, 3, 3, 150.00, 'CARD_PURCHASE', '2026-10-08 09:53:26.815839');
INSERT INTO public.transactions VALUES (5, 68, 3, 3, 250.00, 'CARD_PURCHASE', '2026-10-07 15:27:39.542255');
INSERT INTO public.transactions VALUES (6, 69, 2, 3, 3500.00, 'CARD_PURCHASE', '2026-10-06 15:27:39.542255');
INSERT INTO public.transactions VALUES (7, 69, 3, 3, 2200.00, 'CARD_PURCHASE', '2026-10-07 15:27:39.542255');
INSERT INTO public.transactions VALUES (8, 70, 1, 2, 5000.00, 'CARD_PURCHASE', '2026-10-07 15:27:39.542255');
INSERT INTO public.transactions VALUES (9, 70, 1, 2, 7000.00, 'CARD_PURCHASE', '2026-10-06 15:27:39.542255');
INSERT INTO public.transactions VALUES (10, 70, 1, 2, 6000.00, 'CARD_PURCHASE', '2026-10-05 15:27:39.542255');
INSERT INTO public.transactions VALUES (11, 71, 2, 3, 12000.00, 'CARD_PURCHASE', '2026-10-07 15:27:39.542255');
INSERT INTO public.transactions VALUES (12, 71, 2, 3, 15000.00, 'CARD_PURCHASE', '2026-10-06 15:27:39.542255');
INSERT INTO public.transactions VALUES (13, 72, 3, 3, 450.00, 'CARD_PURCHASE', '2026-10-07 15:27:39.542255');
INSERT INTO public.transactions VALUES (14, 73, 1, 2, 20000.00, 'CARD_PURCHASE', '2026-10-07 15:27:39.542255');
INSERT INTO public.transactions VALUES (15, 73, 1, 2, 18000.00, 'CARD_PURCHASE', '2026-10-06 15:27:39.542255');
INSERT INTO public.transactions VALUES (16, 73, 1, 2, 22000.00, 'CARD_PURCHASE', '2026-10-05 15:27:39.542255');
INSERT INTO public.transactions VALUES (17, 74, 2, 3, 4000.00, 'CARD_PURCHASE', '2026-10-07 15:27:39.542255');
INSERT INTO public.transactions VALUES (18, 74, 2, 3, 3000.00, 'CARD_PURCHASE', '2026-10-05 15:27:39.542255');
INSERT INTO public.transactions VALUES (19, 75, 3, 3, 800.00, 'CARD_PURCHASE', '2026-10-06 15:27:39.542255');
INSERT INTO public.transactions VALUES (20, 76, 2, 3, 9000.00, 'CARD_PURCHASE', '2026-10-07 15:27:39.542255');
INSERT INTO public.transactions VALUES (21, 76, 2, 3, 11000.00, 'CARD_PURCHASE', '2026-10-06 15:27:39.542255');
INSERT INTO public.transactions VALUES (22, 76, 2, 3, 13000.00, 'CARD_PURCHASE', '2026-10-04 15:27:39.542255');
INSERT INTO public.transactions VALUES (23, 77, 1, 2, 30000.00, 'CARD_PURCHASE', '2026-10-07 15:27:39.542255');
INSERT INTO public.transactions VALUES (24, 77, 1, 2, 25000.00, 'CARD_PURCHASE', '2026-10-06 15:27:39.542255');
INSERT INTO public.transactions VALUES (25, 78, 3, 3, 600.00, 'CARD_PURCHASE', '2026-10-05 15:27:39.542255');
INSERT INTO public.transactions VALUES (26, 79, 2, 3, 5000.00, 'CARD_PURCHASE', '2026-10-07 15:27:39.542255');
INSERT INTO public.transactions VALUES (27, 79, 1, 2, 8000.00, 'CARD_PURCHASE', '2026-10-06 15:27:39.542255');


--
-- Data for Name: alerts; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.alerts VALUES (4, 57, 14, 'LARGE_TXN', 'MEDIUM', 'OPEN', 'Transaction of $20,000.00 exceeded synthetic monitoring rule threshold', '2026-10-08 15:36:41.879276');
INSERT INTO public.alerts VALUES (5, 57, 15, 'LARGE_TXN', 'MEDIUM', 'OPEN', 'Transaction of $18,000.00 exceeded synthetic monitoring rule threshold', '2026-10-08 15:36:41.880409');
INSERT INTO public.alerts VALUES (7, 60, 21, 'LARGE_TXN', 'MEDIUM', 'OPEN', 'Transaction of $11,000.00 exceeded synthetic monitoring rule threshold', '2026-10-08 15:36:41.882437');
INSERT INTO public.alerts VALUES (8, 60, 22, 'LARGE_TXN', 'MEDIUM', 'OPEN', 'Transaction of $13,000.00 exceeded synthetic monitoring rule threshold', '2026-10-08 15:36:41.883361');
INSERT INTO public.alerts VALUES (10, 61, 24, 'LARGE_TXN', 'MEDIUM', 'ESCALATED', 'Transaction of $25,000.00 exceeded synthetic monitoring rule threshold', '2026-10-08 15:36:41.884923');
INSERT INTO public.alerts VALUES (6, 57, 16, 'LARGE_TXN', 'MEDIUM', 'UNDER_REVIEW', 'Transaction of $22,000.00 exceeded synthetic monitoring rule threshold', '2026-10-08 15:36:41.881424');
INSERT INTO public.alerts VALUES (9, 61, 23, 'LARGE_TXN', 'MEDIUM', 'UNDER_REVIEW', 'Transaction of $30,000.00 exceeded synthetic monitoring rule threshold', '2026-10-08 15:36:41.884091');
INSERT INTO public.alerts VALUES (2, 55, 11, 'LARGE_TXN', 'MEDIUM', 'ESCALATED', 'Transaction of $12,000.00 exceeded synthetic monitoring rule threshold', '2026-10-08 15:36:41.869742');
INSERT INTO public.alerts VALUES (1, 1, 1, 'LARGE_TXN', 'MEDIUM', 'CLOSED', 'Transaction of $12,000.00 exceeded reporting threshold', '2026-10-08 13:26:20.425422');
INSERT INTO public.alerts VALUES (3, 55, 12, 'LARGE_TXN', 'MEDIUM', 'UNDER_REVIEW', 'Transaction of $15,000.00 exceeded synthetic monitoring rule threshold', '2026-10-08 15:36:41.878154');


--
-- Data for Name: risk_scores; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.risk_scores VALUES (1, 1, '2026-10-08', 14, 9, 23, 'LOW');
INSERT INTO public.risk_scores VALUES (2, 2, '2026-10-08', 10, 8, 18, 'LOW');
INSERT INTO public.risk_scores VALUES (3, 3, '2026-10-08', 2, 0, 2, 'LOW');
INSERT INTO public.risk_scores VALUES (10, 52, '2026-10-08', 2, 0, 2, 'LOW');
INSERT INTO public.risk_scores VALUES (11, 53, '2026-10-08', 9, 8, 17, 'LOW');
INSERT INTO public.risk_scores VALUES (12, 54, '2026-10-08', 24, 27, 51, 'MEDIUM');
INSERT INTO public.risk_scores VALUES (13, 55, '2026-10-08', 31, 16, 47, 'MEDIUM');
INSERT INTO public.risk_scores VALUES (14, 56, '2026-10-08', 2, 0, 2, 'LOW');
INSERT INTO public.risk_scores VALUES (15, 57, '2026-10-08', 60, 27, 87, 'HIGH');
INSERT INTO public.risk_scores VALUES (16, 58, '2026-10-08', 11, 16, 27, 'LOW');
INSERT INTO public.risk_scores VALUES (17, 59, '2026-10-08', 2, 0, 2, 'LOW');
INSERT INTO public.risk_scores VALUES (18, 60, '2026-10-08', 39, 24, 63, 'HIGH');
INSERT INTO public.risk_scores VALUES (19, 61, '2026-10-08', 59, 18, 77, 'HIGH');
INSERT INTO public.risk_scores VALUES (20, 62, '2026-10-08', 2, 0, 2, 'LOW');
INSERT INTO public.risk_scores VALUES (21, 63, '2026-10-08', 17, 17, 34, 'LOW');


--
-- Name: accounts_account_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.accounts_account_id_seq', 80, true);


--
-- Name: alerts_alert_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.alerts_alert_id_seq', 10, true);


--
-- Name: customers_customer_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.customers_customer_id_seq', 64, true);


--
-- Name: geo_locations_geo_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.geo_locations_geo_id_seq', 3, true);


--
-- Name: merchant_types_merchant_type_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.merchant_types_merchant_type_id_seq', 3, true);


--
-- Name: risk_scores_score_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.risk_scores_score_id_seq', 36, true);


--
-- Name: transactions_txn_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.transactions_txn_id_seq', 27, true);


--

--



