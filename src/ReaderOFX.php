<?php

namespace ReaderOFX;

/**
 * Classe ReaderOFX
 *
 * Classe responsável pela comunicação com a API NFHub
 *
 * @package   ReaderOFX
 * @author    Jefferson Moreira <jeematheus at gmail dot com>
 * @copyright 2020 NFSERVICE
 * @license   https://opensource.org/licenses/MIT MIT
 */
class ReaderOFX
{
    private $arquivo;
    public $bankTranList;
    public $dtStar;
    public $dtEnd;
    public $bankId;
    public $acctId;
    public $org;

    /**
     * Função responsável pela inicialização da Classe
     *
     * @param string $arquivo Caminho do arquivo OFX
     *
     * @access public
     * @return SimpleXMLElement
     */
    public function __construct($arquivo)
    {
        $this->arquivo = $arquivo;
        return $this->retorno();
    }

    /**
     * Função responsável por verificar se existem tags não fechadas no arquivo OFX
     *
     * @param string $ofx Caminho do arquivo OFX
     *
     * @access public
     * @return SimpleXMLElement
     */
    public function closeTags($ofx = null)
    {
        $buffer = '';
        $source = fopen($ofx, 'r') or die("Unable to open file!");
        while (!feof($source)) {
            $line = trim(fgets($source));
            if ($line === '') {
                continue;
            }

            if (substr($line, -1, 1) !== '>') {
                list($tag) = explode('>', $line, 2);
                $line .= '</' . substr($tag, 1) . '>';
            }
            $buffer .= $line ."\n";
        }


        $xmlOut =   explode("<OFX>", $buffer);
        return isset($xmlOut[1])?"<OFX>".$xmlOut[1]:$buffer;
    }

    /**
     * Função responsável por retorno o objeto contendo as informações do arquivo OFX
     *
     * @access public
     * @return ReaderOFX
     */
    public function retorno()
    {
        $retorno    =   new \SimpleXMLElement((string)($this->closeTags($this->arquivo)));

        $this->bankTranList =   $retorno->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN;
        $this->dtStar   =   $retorno->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->DTSTART;
        $this->dtEnd    =   $retorno->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->DTEND;

        $this->org      =   $retorno->SIGNONMSGSRSV1->SONRS->FI->ORG;
        $this->acctId   =   $retorno->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKACCTFROM->ACCTID;
        $this->bankId   =   $retorno->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKACCTFROM->BANKID;

        return $this;
    }

    /**
     * Função responsável por transformar a instancia SimpleXMLElement em um array
     *
     * @access public
     * @return array
     */
    public function getArray()
    {
        $dados = [
            'dtStar' => (string)$this->dtStar,
            'dtEnd' => (string)$this->dtEnd,
            'bankId' => (string)$this->bankId,
            'acctId' => (string)$this->acctId,
            'org' => (string)$this->org,
            'bankTranList' => []
        ];
        foreach ($this->bankTranList as $key => $extrato) {
            $dados['bankTranList'][] = (array)$extrato;
        }

        return $dados;
    }
}
