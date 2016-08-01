<?php

namespace uc\server\app\model;

use uc\server\Table;

/**
 * 邮件队列管理模型
 */
class Mail extends Table
{
    const UC_MAIL_REPEAT = 5;
    
    protected $name = 'mailqueue';

    public function get_total_num()
    {
        return $this->from($this->getName())->count();
    }

    public function get_list($page, $ppp, $totalnum)
    {
        $start = $this->base->page_get_start($page, $ppp, $totalnum);
        $data = $this->select('m.*, u.username, u.email')
                ->from($this->getName() . ' m')
                ->leftJoin('{{%members}} u', 'm.touid=u.uid')
                ->orderBy(['dateline' => SORT_DESC])
                ->offset($start)
                ->limit($ppp)
                ->all();
        foreach ((array) $data as $k => $v) {
            $data[$k]['subject'] = dhtmlspecialchars($v['subject']);
            $data[$k]['tomail'] = empty($v['tomail']) ? $v['email'] : $v['tomail'];
            $data[$k]['dateline'] = $v['dateline'] ? $this->base->date($data[$k]['dateline']) : '';
            $data[$k]['appname'] = $this->base->cache['apps'][$v['appid']]['name'];
        }
        return $data;
    }

    /**
     * 
     * @param array $ids
     * @return int
     */
    public function delete_mail(array $ids)
    {
        return $this->getCommand()->delete($this->getName(), ['mailid' => $ids]);
    }

    public function add(array $mail)
    {
        if ($mail['level']) {
            $sql = "INSERT INTO {{%mailqueue}} (touid, tomail, subject, message, frommail, charset, htmlon, level, dateline, failures, appid) VALUES ";
            $values_arr = array();
            foreach ($mail['uids'] as $uid) {
                if (empty($uid))
                    continue;
                $values_arr[] = "('$uid', '', '{$mail['subject']}', '{$mail['message']}', '{$mail['frommail']}', '{$mail['charset']}', '{$mail['htmlon']}', '{$mail['level']}', '{$mail['dateline']}', '0', '{$mail['appid']}')";
            }
            foreach ($mail['emails'] as $email) {
                if (empty($email))
                    continue;
                $values_arr[] = "('', '$email', '{$mail['subject']}', '{$mail['message']}', '{$mail['frommail']}', '{$mail['charset']}', '{$mail['htmlon']}', '{$mail['level']}', '{$mail['dateline']}', '0', '{$mail['appid']}')";
            }
            $sql .= implode(',', $values_arr);
            $this->db->execute($sql);
            $insert_id = $this->db->insert_id();
            $insert_id && $this->db->execute("REPLACE INTO {{%vars}} SET name='mailexists', value='1'");
            return $insert_id;
        } else {
            $mail['email_to'] = [];
            $users = $this->select('uid, username, email')
                    ->from('{{%members}}')
                    ->where($mail['uids'])
                    ->all();
            foreach ($users as $v) {
                $mail['email_to'][] = $v['username'] . '<' . $v['email'] . '>';
            }
            foreach ($mail['emails'] as $email) {
                if (empty($email))
                    continue;
                $mail['email_to'][] = $email;
            }
            $mail['message'] = str_replace('\"', '"', $mail['message']);
            $mail['email_to'] = implode(',', $mail['email_to']);
            return $this->send_one_mail($mail);
        }
    }

    public function send()
    {
        register_shutdown_function(array(
            $this,
            '_send'
        ));
    }

    private function _send()
    {
        $mail = $this->_get_mail();
        if (empty($mail)) {
            $this->db->execute("REPLACE INTO {{%vars}} SET name='mailexists', value='0'");
            return NULL;
        }
        
        $mail['email_to'] = $mail['tomail'] ? $mail['tomail'] : $mail['username'] . '<' . $mail['email'] . '>';
        if ($this->send_one_mail($mail)) {
            $this->_delete_one_mail($mail['mailid']);
            return true;
        } else {
            $this->_update_failures($mail['mailid']);
            return false;
        }
    }

    public function send_by_id(int $mailid)
    {
        if ($this->send_one_mail($this->_get_mail_by_id($mailid))) {
            $this->_delete_one_mail($mailid);
            return true;
        }
    }

    public function send_one_mail(array $mail)
    {
        if (empty($mail))
            return;
        $mail['email_to'] = $mail['email_to'] ? $mail['email_to'] : $mail['username'] . '<' . $mail['email'] . '>';
        return (new \uc\server\Mailer($this->base->settings))
                ->send($mail, $this->base->cache['apps'][$mail['appid']]['name']);
    }

    private function _get_mail()
    {
        return $this->select('m.*, u.username, u.email')
                ->from($this->getName() . ' m')
                ->leftJoin('{{%members}} u', 'm.touid=u.uid')
                ->where(['<', 'failures', self::UC_MAIL_REPEAT])
                ->orderBy(['level' => SORT_DESC, 'mailid' => SORT_ASC])
                ->one();
    }

    private function _get_mail_by_id(int $mailid)
    {
        return $this->select('m.*, u.username, u.email')
                ->from($this->getName() . ' m')
                ->leftJoin('{{%members}} u', 'm.touid=u.uid')
                ->where(['mailid' => $mailid])
                ->one();
    }

    private function _delete_one_mail(int $mailid)
    {
        return $this->delete(['mailid' => $mailid]);
    }

    private function _update_failures(int $mailid)
    {
        return $this->update(['failures' => 'failures+1'], ['mailid' => $mailid]);
    }
}
