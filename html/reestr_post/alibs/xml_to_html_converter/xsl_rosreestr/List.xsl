<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:variable name="ExtractCount" select="count(//ReestrExtract/ExtractObjectRight)"/>
  <xsl:variable name="NoticeCount" select="count(//ReestrExtract/NoticelObj)"/>
  <xsl:variable name="RefusalCount" select="count(//ReestrExtract/RefusalObj)"/>
  <xsl:template match="Extract[eDocument[@Version='07']]">
    <html>
      <head>
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>Выписка из ЕГРП о переходе прав на объект (версия 07)</title>

        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
        <meta name="Content-Script-Type" content="text/javascript"/>
        <meta name="Content-Style-Type" content="text/css"/>
        <style type="text/css">body{border: 0px solid black; background:#fff;color:#000;font-family:Tahoma,arial,sans-serif}body,th,font.signature,th.signature,th.pager,.page_title,.topstroke,div.procherk,.td_center,.tbl_clear,.tbl_clear TD,.tbl_section_sign TD,.small_text{text-align:center}th,td{font:10pt Tahoma,arial,sans-serif;color:black;FONT-WEIGHT:normal}table{border-collapse:collapse;empty-cells:show}div.floatR{float:right}div.floatL{float:left}th.head{background:White;width:3%;font-weight:bold}th.head,.tbl_page TD,.topstroke,div.procherk,.tbl_section_content TD{vertical-align:top}font.signature,th.signature{font-size:60%}th.signature{font-weight:bolder}th.pager{font-weight:normal}div.page{page-break-before:always}.tbl_page{width:950px;height:700px;border:1px solid #ccc}.tbl_page,.page_title,.topstroke,.td_center{margin:0 auto}.tbl_page,.tbl_section_date TD{text-align:left}.tbl_page TD{padding-top:20px;padding-left:10px;padding-right:10px}.page_title,.tbl_section_title TD{font:bold small Arial,Verdana,Geneva,Helvetica,sans-serif}.page_title,.topstroke{width:90%}.page_title,.understroke{border-bottom:solid;border-bottom-width:1px}.topstroke{border-top:solid;border-top-width:1px}.topstroke,.small_text{font:normal xx-small Arial,Verdana,Geneva,Helvetica,sans-serif}div.procherk{width:10px}div.procherk,.tbl_section_title,.tbl_clear,.tbl_section_sign{width:100%}.tbl_section_title,.tbl_section_date,.tbl_clear,.tbl_section_sign{border:none}.tbl_section_title TD,.tbl_section_date TD,.tbl_section_content TD,.tbl_clear,.tbl_section_sign,.tbl_section_sign TD{padding:0;margin:0}.tbl_section_title TD{padding-top:10px;padding-bottom:10px}.tbl_section_date,.tbl_section_sign{border-color:black}.tbl_section_date TD{padding-right:2px;font:normal x-small Arial,Verdana,Geneva,Helvetica,sans-serif}.tbl_section_content,.tbl_section_content TD{border:1px solid #000}.tbl_section_content TD{padding:4px 3px}.tbl_section_content TD,.tbl_clear TD{font:normal 9pt Arial,Verdana,Geneva,Helvetica,sans-serif}.td_center,.tbl_clear,.tbl_clear TD,.tbl_section_sign TD{vertical-align:middle}.tbl_section_sign{font:bold x-small Arial,Verdana,Geneva,Helvetica,sans-serif}.tbl_section_sign TD{font:normal 10pt Arial,Verdana,Geneva,Helvetica,sans-serif}.windows{height:300px;overflow-y:auto;overflow-x:hidden;scrollbar-face-color:#ccc;scrollbar-shadow-color:Black;scrollbar-highlight-color:#fff;scrollbar-arrow-color:black;scrollbar-base-color:Gray;scrollbar-3dlight-color:#eee;scrollbar-darkshadow-color:#333;scrollbar-track-color:#999}
        .tbl_container{width:100%;border-collapse:collapse;border:0;padding:1px}
        div.title1{text-align:right;padding-right:10px;font-size:100%}div.title2{margin-left:auto;margin-right:auto;font-size:100%;text-align:center;}
        .tbl_section_topsheet{width:100%;border-collapse:collapse;border:1px solid #000;padding:1px}.tbl_section_topsheet th,.tbl_section_topsheet td.in16{border:1px solid #000;vertical-align:middle;margin:0;padding:4px 3px}.tbl_section_topsheet th.left,.tbl_section_topsheet td.left{text-align:left}.tbl_section_topsheet th.vtop,.tbl_section_topsheet td.vtop{vertical-align:top}
        .tbl_section_date{border:none;border-color:#000}.tbl_section_date td{text-align:left;margin:0;padding:0 3px}.tbl_section_date td.nolpad{padding-left:0}.tbl_section_date td.norpad{padding-right:0}.tbl_section_date td.understroke{border-bottom:1px solid #000}
        @media print{p.pagebreak{page-break-before: always;}.noPrint{display:none;}@page{size:landscape;margin:0;margin-top:5px;}.Footer{display:none;}body{margin:0;}}
        .t td{text-align:left}
        </style>


        <!-- <style type="text/css">
          html, body, table { font: 12pt Tahoma }
          p, .t { margin: 10pt 0 10pt 0 }
          tr, .note { vertical-align: top }
          table, .file { text-align: justify }
          .file { width: 17cm }
          .edoc, .ndoc, .rdoc { margin-bottom: 2cm }
          <xsl:if test="$ExtractCount = 1 and ($NoticeCount + $RefusalCount) > 0">
            .edoc { page-break-after: always }
          </xsl:if>
          <xsl:if test="($NoticeCount + $RefusalCount) = 2">
            .ndoc { page-break-after: always }
          </xsl:if>
          .vc { vertical-align: middle }
          .ul, .ful, .note, .c { text-align: center }
          .ul, .ful { vertical-align: bottom; border-bottom: solid 1pt black }
          .ful { font-size: 85% }
          .note { font-size: 50%; line-height: normal }
          .sr { padding: 0 5pt 0 0 }
          .NoticeHeaderMargin, .NoticeHeaderHeight, .ExtractHeaderMargin { display: none }
          body { border: 0px solid black; }
          @media print
          {
          body { margin: 0;}
          .file { width: 100% }
          .edoc, .ndoc, .rdoc { margin-bottom: 0 }
          }
        </style> -->
      </head>
      <body>
        <div class="file">
          <xsl:apply-templates />
        </div>
      </body>
    </html>
  </xsl:template>

  <xsl:template match="ExtractObjectRight">
    <div class="edoc">
      <xsl:call-template name="Header">
        <xsl:with-param name="Header" select="HeadContent"/>
      </xsl:call-template>
      <xsl:call-template name="Extract">
        <xsl:with-param name="Extract" select="ExtractObject"/>
      </xsl:call-template>
      <xsl:call-template name="Footer">
        <xsl:with-param name="Footer" select="FootContent" />
      </xsl:call-template>
    </div>
  </xsl:template>

  <xsl:template match="NoticelObj">
    <div class="ndoc">
      <xsl:call-template name="Header">
        <xsl:with-param name="Header" select="HeadContent"/>
        <xsl:with-param name="ExtractExists" select="$ExtractCount > 0"/>
        <xsl:with-param name="Recipient" select="parent::ReestrExtract/DeclarAttribute/ReceivName"/>
        <xsl:with-param name="Agent" select="parent::ReestrExtract/DeclarAttribute/Representativ"/>
        <xsl:with-param name="Address" select="parent::ReestrExtract/DeclarAttribute/ReceivAdress"/>
      </xsl:call-template>
      <xsl:call-template name="Notice">
        <xsl:with-param name="Notice" select="NoticeObj"/>
      </xsl:call-template>
      <xsl:call-template name="Footer">
        <xsl:with-param name="Footer" select="FootContent" />
        <xsl:with-param name="IsDuplicate" select="$ExtractCount > 0"/>
      </xsl:call-template>
    </div>
  </xsl:template>

  <xsl:template match="RefusalObj">
    <div class="rdoc">
      <xsl:call-template name="Header">
        <xsl:with-param name="Header" select="HeadContent"/>
        <xsl:with-param name="ExtractExists" select="$ExtractCount > 0"/>
        <xsl:with-param name="NoticeExists" select="$NoticeCount > 0"/>
        <xsl:with-param name="Recipient" select="parent::ReestrExtract/DeclarAttribute/ReceivName"/>
        <xsl:with-param name="Agent" select="parent::ReestrExtract/DeclarAttribute/Representativ"/>
        <xsl:with-param name="Address" select="parent::ReestrExtract/DeclarAttribute/ReceivAdress"/>
      </xsl:call-template>
      <xsl:call-template name="Refusal">
        <xsl:with-param name="Refusal" select="RefusalObj"/>
      </xsl:call-template>
      <xsl:call-template name="Footer">
        <xsl:with-param name="Footer" select="FootContent" />
        <xsl:with-param name="IsDuplicate" select="($ExtractCount + $NoticeCount) > 0"/>
      </xsl:call-template>
    </div>
  </xsl:template>

  <xsl:template match="DeclarAttribute" />
</xsl:stylesheet>
