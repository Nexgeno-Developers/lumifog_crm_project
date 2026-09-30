<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Kpi_rule_engine
{
    /**
     * @param array|string|null $rule_json
     * @param array<mixed>      $context
     * @return array{points:int,matched_condition:mixed,status:string,context:mixed}
     */
    public static function calculatePoints($rule_json, $context = [])
    {
        $engine = new self();

        return $engine->run($rule_json, $context);
    }

    /**
     * @param array|string|null $rule_json
     * @param array<mixed>      $context
     */
    private function run($rule_json, $context = [])
    {
        $rule = is_array($rule_json) ? $rule_json : json_decode($rule_json, true);

        if (!$rule || !isset($rule['rule'])) {
            return $this->response(0, null, false, ['message' => 'Invalid rule']);
        }

        $ruleName = $rule['rule'];

        // ==========================================
        // RULE: EARLY / ON TIME
        // ==========================================
        if ($ruleName === 'early_and_on_time_arrival') {

            $diff = $this->timeDiff(
                $context['checkin_time'] ?? '',
                $context[$rule['based_on']] ?? ''
            );

            foreach ($rule['conditions'] as $cond) {
                if ($diff >= $cond['from'] && $diff <= $cond['to']) {
                    return $this->response($cond['points'], $cond, true, $context);
                }
            }
        }

        // ==========================================
        // RULE: LATE ARRIVAL
        // ==========================================
        if ($ruleName === 'late_arrival') {

            $diff = $this->timeDiff(
                $context['checkin_time'] ?? '',
                $context[$rule['based_on']] ?? ''
            );

            foreach ($rule['conditions'] as $cond) {
                if ($diff >= $cond['from'] && $diff <= $cond['to']) {
                    return $this->response($cond['points'], $cond, true, $context);
                }
            }
        }

        // ==========================================
        // RULE: ABSENT
        // ==========================================
        if ($ruleName === 'absent') {
            $points = isset($rule['points']) ? (int) $rule['points'] : -6;
            $cond = ['rule' => 'absent', 'points' => $points, 'remarks' => 'Absent'];
            return $this->response($points, $cond, true, $context);
        }

        return $this->response(0, null, false, $context);
    }

    // ==========================================
    // COMMON FUNCTION
    // ==========================================
    private function timeDiff($t1, $t2)
    {
        if (!$t1 || !$t2) {
            return 0;
        }

        return round((strtotime($t1) - strtotime($t2)) / 60);
    }

    /**
     * @param mixed $context
     */
    private function response($points, $condition, $matched, $context = [])
    {
        return [
            'points'            => $points,
            'matched_condition' => $condition,
            'status'            => $matched ? 'success' : 'failed',
            'context'           => $context,
        ];
    }
}
